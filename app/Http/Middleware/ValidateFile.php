<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Lib\ConvertUnit;
use Illuminate\Support\Facades\DB;

class ValidateFile
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */

    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->isMethod('POST')) {
            return response('Solo se admiten peticiones POST', 405);
        } 

        if (!$request->file('file')) { 
            return response('No se ha enviado ningun archivo', 400);
        } 

        $fileHash = hash_file('sha256', $request->file('file'));
        $isFileBlackListed = DB::select('select * from niggalist where hash = :hash', ['hash' => $fileHash]);

        if($isFileBlackListed){
            return response('Este archivo ha sido bloqueado', 403);
        }

        $fileSize = new ConvertUnit();
        $fileSize = $fileSize->byteToMB($request->file('file')->getSize());

        if ($fileSize > 150) {
            return response('El archivo supera el máximo permitido', 413);
        }

        $extension = $request->file('file')->getClientOriginalExtension(); 

        if (!$this->isValidExtension($extension)) { 
            return response('Tipo de archivo no admitido', 400);
        }

        if($this->needsSanitize($extension)){ 
            $request->merge(['needsSanitize' => true]);
            return $next($request);
        }

        return $next($request);
    }

    private function isValidExtension($extension)
    {
        $blacklist = ['jsp', 'exe', 'jar', 'scr', 'cpl', 'doc', 'docx', 'sh'];
        return !in_array($extension, $blacklist);
    }

    private function needsSanitize($extension)
    {
        $required = ['html', 'xhtml', 'php', 'phtml', 'cgi', 'xml', 'js'];
        return in_array($extension, $required);
    }
}