<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;

class CategoryController extends Controller
{
  // Get all video categories
  public function index() {
    return Category::all();
  }
  
    // Add new category of video
  public function store(Request $request) {
    $request->validate([
      'name' => 'required|string|unique:categories',
    ]);
    $result = Category::create($request->only('name'));
    if ($result) {
      return ['success' => true,
        'message' => 'Category successfully added!'];
    }
    return response()->json(['success' => false, 'message' => 'failed to add category!'], 451);
  }

  // Delete category
  public function destroy($id) {
    if (Category::findOrFail($id)->delete()) {
      return ['success' => true,
        'message' => 'Category successfully deleted!'];
    }
    return response()->json(['success' => false, 'message' => 'failed to delete category!'], 451);
  }
  

}