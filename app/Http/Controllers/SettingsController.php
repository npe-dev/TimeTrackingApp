<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    private $extensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    public function backgroundStatus()
    {
        foreach ($this->extensions as $ext) {
            if (Storage::disk('public')->exists("backgrounds/background.{$ext}")) {
                return response()->json([
                    'exists' => true,
                    'url' => '/storage/backgrounds/background.' . $ext . '?t=' . time(),
                ]);
            }
        }
        return response()->json(['exists' => false]);
    }

    public function uploadBackground(Request $request)
    {
        $request->validate([
            'background' => 'required|image|max:10240',
        ]);

        $file = $request->file('background');
        $ext = strtolower($file->getClientOriginalExtension()) ?: 'jpg';

        // Remove old backgrounds
        foreach ($this->extensions as $e) {
            Storage::disk('public')->delete("backgrounds/background.{$e}");
        }

        $file->storeAs('backgrounds', "background.{$ext}", 'public');

        return response()->json([
            'success' => true,
            'url' => '/storage/backgrounds/background.' . $ext . '?t=' . time(),
        ]);
    }

    public function deleteBackground()
    {
        foreach ($this->extensions as $ext) {
            Storage::disk('public')->delete("backgrounds/background.{$ext}");
        }
        return response()->json(['success' => true]);
    }
}
