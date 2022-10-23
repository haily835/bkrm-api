<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Storage;

class Util {
    static function saveImage($image, $directory) {
        $imagePath = $image->store('uploaded_images/' . $directory, 'public');
        $url = Storage::url($imagePath);
        return env('APP_URL') . $url;
    }
} 
