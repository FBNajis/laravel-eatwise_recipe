<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Recipe;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class RecipeController extends Controller
{
    public function allRecipes()
    {
        $recipes = Recipe::select('id', 'user_id', 'name', 'description', 'cost_estimation', 'cooking_time', 'ingredients', 'instructions', 'tag', 'image_path', 'created_at')
            ->with(['user:id,fullname'])
            ->withCount('favorites')
            ->approved()
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($recipe) => $this->formatRecipeResponse($recipe));

        return response()->json([
            'message' => 'All recipes retrieved successfully',
            'data' => $recipes,
        ]);
    }

    public function randomRecipes()
    {
        $recipes = Recipe::select('id', 'user_id', 'name', 'description', 'cost_estimation', 'cooking_time', 'ingredients', 'instructions', 'tag', 'image_path', 'created_at')
            ->with(['user:id,fullname'])
            ->withCount('favorites')
            ->approved()
            ->inRandomOrder()
            ->take(2)
            ->get()
            ->map(fn($recipe) => $this->formatRecipeResponse($recipe));

        return response()->json([
            'message' => 'Random recipes retrieved successfully',
            'data' => $recipes,
        ]);
    }

    public function topRecipes(Request $request)
    {
        $limit = $request->get('limit');

        $query = Recipe::select('id', 'user_id', 'name', 'description', 'cost_estimation', 'cooking_time', 'ingredients', 'instructions', 'tag', 'image_path', 'created_at')
            ->with(['user:id,fullname'])
            ->withCount('favorites')
            ->approved()
            ->orderBy('favorites_count', 'desc');

        if ($limit) {
            $query->take((int) $limit);
        }

        $recipes = $query->get()->map(fn($recipe) => $this->formatRecipeResponse($recipe));

        return response()->json([
            'message' => 'Top liked recipes retrieved successfully',
            'data' => $recipes,
        ]);
    }

    public function catRecipes(Request $request)
    {
        $category = $request->get('category');

        $recipes = Recipe::select('id', 'user_id', 'name', 'description', 'cost_estimation', 'cooking_time', 'ingredients', 'instructions', 'tag', 'image_path', 'created_at')
            ->with(['user:id,fullname'])
            ->withCount('favorites')
            ->approved()
            ->where('tag', $category)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($recipe) => $this->formatRecipeResponse($recipe));

        return response()->json([
            'message' => "Recipes in category '$category' retrieved successfully",
            'data' => $recipes,
        ]);
    }

    public function budRecipes(Request $request)
    {
        $budget = $request->get('budget');

        $query = Recipe::select('id', 'user_id', 'name', 'description', 'cost_estimation', 'cooking_time', 'ingredients', 'instructions', 'tag', 'image_path', 'created_at')
            ->with(['user:id,fullname'])
            ->withCount('favorites')
            ->approved();
            
        if ($budget == '<15K') {
            $query->where('cost_estimation', '<', 15000);
        } elseif ($budget == '15K - 30K') {
            $query->whereBetween('cost_estimation', [15000, 30000]);
        } elseif ($budget == '30K - 50K') {
            $query->whereBetween('cost_estimation', [30000, 50000]);
        } elseif ($budget == '50K - 100K') {
            $query->whereBetween('cost_estimation', [50000, 100000]);
        } elseif ($budget == '>100K') {
            $query->where('cost_estimation', '>', 100000);
        }
        
        $recipes = $query->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($recipe) => $this->formatRecipeResponse($recipe));

        return response()->json([
            'message' => "Recipes under budget '$budget' retrieved successfully",
            'data' => $recipes,
        ]);
    }

    public function searchRecipes(Request $request)
    {
        $query = $request->get('query');

        $recipes = Recipe::select('id', 'user_id', 'name', 'description', 'cost_estimation', 'cooking_time', 'ingredients', 'instructions', 'tag', 'image_path', 'created_at')
            ->with(['user:id,fullname'])
            ->withCount('favorites')
            ->approved()
            ->where('name', 'like', "%$query%")
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($recipe) => $this->formatRecipeResponse($recipe));

        return response()->json([
            'message' => 'Recipes search results',
            'data' => $recipes,
        ]);
    }

    public function store(Request $request)
    {
        Log::info('Creating new recipe', ['user_id' => $request->user()->id]);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'cost_estimation' => 'required|numeric|min:0',
            'cooking_time' => 'required|integer|min:1',
            'ingredients' => 'required|string',
            'instructions' => 'required|string',
            'tag' => 'required|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:25600',
        ]);

        $imagePath = $request->hasFile('image')
            ? $request->file('image')->store('recipes', 'public')
            : null;

        $recipe = Recipe::create([
            'user_id' => $request->user()->id,
            ...$validated,
            'image_path' => $imagePath,
            'status' => 'approved' // ✅ sementara langsung approved, ubah ke 'pending' jika nanti butuh approval admin
        ]);

        return response()->json([
            'message' => 'Recipe created successfully',
            'data' => $this->formatRecipeResponse($recipe)
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $recipe = Recipe::findOrFail($id);

        if ($request->user()->id !== $recipe->user_id) {
            return response()->json([
                'message' => 'Unauthorized - You can only update your own recipes'
            ], 403);
        }

        Log::info('Updating recipe', ['recipe_id' => $id, 'user_id' => $request->user()->id]);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'cost_estimation' => 'required|numeric|min:0',
            'cooking_time' => 'required|integer|min:1',
            'ingredients' => 'required|string',
            'instructions' => 'required|string',
            'tag' => 'required|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:25600',
        ]);

        if ($request->hasFile('image')) {
            if ($recipe->image_path) {
                Storage::disk('public')->delete($recipe->image_path);
            }
            $validated['image_path'] = $request->file('image')->store('recipes', 'public');
        } else {
            $validated['image_path'] = $recipe->image_path;
        }

        $recipe->update($validated);

        return response()->json([
            'message' => 'Recipe updated successfully',
            'data' => $this->formatRecipeResponse($recipe)
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $recipe = Recipe::findOrFail($id);

        if ($request->user()->id !== $recipe->user_id) {
            return response()->json([
                'message' => 'Unauthorized - You can only delete your own recipes'
            ], 403);
        }

        Log::info('Deleting recipe', ['recipe_id' => $id, 'user_id' => $request->user()->id]);

        if ($recipe->image_path) {
            Storage::disk('public')->delete($recipe->image_path);
        }

        $recipe->delete();

        return response()->json([
            'message' => 'Recipe deleted successfully'
        ]);
    }

    public function userRecipes(Request $request)
    {
        $recipes = Recipe::with(['user:id,fullname'])
            ->withCount('favorites')
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($recipe) => $this->formatRecipeResponse($recipe))
            ->groupBy(fn($recipe) => Carbon::parse($recipe['created_at'])->format('l, d F Y'));

        return response()->json([
            'message' => 'User recipes retrieved successfully',
            'data' => $recipes,
        ]);
    }

    public function like(Request $request, $id)
    {
        $recipe = Recipe::findOrFail($id);
        $request->user()->favoriteRecipes()->syncWithoutDetaching([$recipe->id]);

        return response()->json([
            'message' => 'Recipe liked successfully',
        ]);
    }

    public function unlike(Request $request, $id)
    {
        $recipe = Recipe::findOrFail($id);
        $request->user()->favoriteRecipes()->detach($recipe->id);

        return response()->json([
            'message' => 'Recipe unliked successfully',
        ]);
    }

    public function likedRecipes(Request $request)
    {
        $recipes = $request->user()->favoriteRecipes()
            ->with(['user:id,fullname'])
            ->withCount('favorites')
            ->approved()
            ->orderBy('favorites_count', 'desc')
            ->get()
            ->map(fn($recipe) => $this->formatRecipeResponse($recipe));

        return response()->json([
            'message' => 'Liked recipes retrieved successfully',
            'data' => $recipes,
        ]);
    }

    private function formatRecipeResponse($recipe)
    {
        return [
            'id' => $recipe->id,
            'name' => $recipe->name,
            'description' => $recipe->description,
            'cost_estimation' => $recipe->cost_estimation,
            'cooking_time' => $recipe->cooking_time,
            'ingredients' => $recipe->ingredients,
            'instructions' => $recipe->instructions,
            'tag' => $recipe->tag,
            'image_path' => $recipe->image_path ? url('storage/' . $recipe->image_path) : null,
            'created_at' => $recipe->created_at,
            'favorites_count' => $recipe->favorites_count ?? $recipe->favorites()->count(),
            'creator_name' => $recipe->user->fullname,
        ];
    }
}
