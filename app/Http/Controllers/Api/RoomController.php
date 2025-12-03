<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RoomController extends Controller
{
    public function index(Request $request)
    {

        return response()->json(['data' => Venue::all()]);

        $query = Room::query();

        // Search functionality
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('bed_type', 'like', "%{$search}%");
            });
        }

        // Get total count before pagination
        $total = $query->count();

        // Pagination
        $perPage = $request->get('per_page', 10);
        $page = $request->get('page', 1);
        
        $rooms = $query->orderBy('created_at', 'desc')
                      ->skip(($page - 1) * $perPage)
                      ->take($perPage)
                      ->get();

        return response()->json([
            'data' => $rooms,
            'total' => $total,
            'per_page' => (int)$perPage,
            'current_page' => (int)$page,
            'last_page' => ceil($total / $perPage),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'capacity' => 'required|integer|min:1',
            'size' => 'required|string',
            'bed_type' => 'required|string',
            'amenities' => 'required|array',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'panorama_images.*' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'panorama_names' => 'nullable|array',
        ]);

        // Handle main image upload
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '_' . Str::slug($request->name) . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('uploads/rooms'), $imageName);
            $validated['image'] = '/uploads/rooms/' . $imageName;
        }

        // Handle multiple images upload
        if ($request->hasFile('images')) {
            $imagesPaths = [];
            foreach ($request->file('images') as $img) {
                $imgName = time() . '_' . Str::random(10) . '.' . $img->getClientOriginalExtension();
                $img->move(public_path('uploads/rooms'), $imgName);
                $imagesPaths[] = '/uploads/rooms/' . $imgName;
            }
            $validated['images'] = $imagesPaths;
        }

        // Handle panorama images upload
        if ($request->hasFile('panorama_images')) {
            $panoramas = [];
            $panoramaNames = $request->input('panorama_names', []);
            
            foreach ($request->file('panorama_images') as $index => $panoramaImg) {
                $panoramaName = time() . '_panorama_' . Str::random(10) . '.' . $panoramaImg->getClientOriginalExtension();
                $panoramaImg->move(public_path('uploads/rooms/panoramas'), $panoramaName);
                
                $panoramas[] = [
                    'id' => Str::slug($panoramaNames[$index] ?? 'view-' . ($index + 1)),
                    'name' => $panoramaNames[$index] ?? 'View ' . ($index + 1),
                    'panoramaUrl' => '/uploads/rooms/panoramas/' . $panoramaName,
                    'thumbnail' => '/uploads/rooms/panoramas/' . $panoramaName,
                ];
            }
            $validated['panoramas'] = $panoramas;
        }

        $room = Room::create($validated);

        return response()->json([
            'message' => 'Room created successfully',
            'data' => $room
        ], 201);
    }

    public function show($id)
    {
        $room = Room::findOrFail($id);
        return response()->json(['data' => $room]);
    }

    public function update(Request $request, $id)
    {
        $room = Room::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'price' => 'sometimes|required|numeric|min:0',
            'capacity' => 'sometimes|required|integer|min:1',
            'size' => 'sometimes|required|string',
            'bed_type' => 'sometimes|required|string',
            'amenities' => 'sometimes|required|array',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'panorama_images.*' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'panorama_names' => 'nullable|array',
        ]);

        // Handle main image upload
        if ($request->hasFile('image')) {
            // Delete old image
            if ($room->image && file_exists(public_path($room->image))) {
                unlink(public_path($room->image));
            }
            
            $image = $request->file('image');
            $imageName = time() . '_' . Str::slug($request->name ?? $room->name) . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('uploads/rooms'), $imageName);
            $validated['image'] = '/uploads/rooms/' . $imageName;
        }

        // Handle multiple images upload
        if ($request->hasFile('images')) {
            // Delete old images
            if ($room->images) {
                foreach ($room->images as $oldImg) {
                    if (file_exists(public_path($oldImg))) {
                        unlink(public_path($oldImg));
                    }
                }
            }

            $imagesPaths = [];
            foreach ($request->file('images') as $img) {
                $imgName = time() . '_' . Str::random(10) . '.' . $img->getClientOriginalExtension();
                $img->move(public_path('uploads/rooms'), $imgName);
                $imagesPaths[] = '/uploads/rooms/' . $imgName;
            }
            $validated['images'] = $imagesPaths;
        }

        // Handle panorama images upload
        if ($request->hasFile('panorama_images')) {
            // Delete old panorama images
            if ($room->panoramas) {
                foreach ($room->panoramas as $oldPanorama) {
                    if (isset($oldPanorama['panoramaUrl']) && file_exists(public_path($oldPanorama['panoramaUrl']))) {
                        unlink(public_path($oldPanorama['panoramaUrl']));
                    }
                }
            }

            $panoramas = [];
            $panoramaNames = $request->input('panorama_names', []);
            
            foreach ($request->file('panorama_images') as $index => $panoramaImg) {
                $panoramaName = time() . '_panorama_' . Str::random(10) . '.' . $panoramaImg->getClientOriginalExtension();
                $panoramaImg->move(public_path('uploads/rooms/panoramas'), $panoramaName);
                
                $panoramas[] = [
                    'id' => Str::slug($panoramaNames[$index] ?? 'view-' . ($index + 1)),
                    'name' => $panoramaNames[$index] ?? 'View ' . ($index + 1),
                    'panoramaUrl' => '/uploads/rooms/panoramas/' . $panoramaName,
                    'thumbnail' => '/uploads/rooms/panoramas/' . $panoramaName,
                ];
            }
            $validated['panoramas'] = $panoramas;
        }

        $room->update($validated);

        return response()->json([
            'message' => 'Room updated successfully',
            'data' => $room
        ]);
    }

    public function destroy($id)
    {
        $room = Room::findOrFail($id);

        // Delete images
        if ($room->image && file_exists(public_path($room->image))) {
            unlink(public_path($room->image));
        }

        if ($room->images) {
            foreach ($room->images as $img) {
                if (file_exists(public_path($img))) {
                    unlink(public_path($img));
                }
            }
        }

        // Delete panorama images
        if ($room->panoramas) {
            foreach ($room->panoramas as $panorama) {
                if (isset($panorama['panoramaUrl']) && file_exists(public_path($panorama['panoramaUrl']))) {
                    unlink(public_path($panorama['panoramaUrl']));
                }
            }
        }

        $room->delete();

        return response()->json([
            'message' => 'Room deleted successfully'
        ]);
    }

    
}