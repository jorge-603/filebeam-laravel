<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Lib\RandomString;
use Illuminate\Support\Facades\Storage;

class SubmitController extends Controller
{
    public function submit(Request $request)
    {
        $fileExtension = $request->file('file')->getClientOriginalExtension();
        $newFileName = new RandomString();
        $newFileName = $newFileName->randomize(6) . '.' . $fileExtension;

        while (file_exists(public_path() . '/file/' . $newFileName)) {
            $newFileName = new RandomString();
            $newFileName = $newFileName->randomize(6) . '.' . $fileExtension;
        }

        if ($request['needsSanitize']) { // Si el archivo requiere ser sanitizado
            $file = $request->file('file');
            $purifiedContent = file_get_contents($file->getPathname());
            $purifiedContent = htmlspecialchars($purifiedContent, ENT_QUOTES | ENT_HTML5);
            Storage::put('public/' . $newFileName, $purifiedContent);
            $url = 'https://127.0.0.1:8000' . '/file/' . $newFileName;
            return response($url, 200);
        }

        $request->file('file')->storeAs('public', $newFileName);
        $url = 'https://127.0.0.1:8000' . '/file/' . $newFileName;
        return response($url, 200);     
    }

}
