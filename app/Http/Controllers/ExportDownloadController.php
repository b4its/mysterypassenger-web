<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportDownloadController extends Controller
{
    public function __invoke(Request $request): StreamedResponse
    {
        try {
            $path = decrypt((string) $request->query('path'));
        } catch (DecryptException) {
            abort(404);
        }

        abort_unless(is_string($path) && str_starts_with($path, 'exports/'), 404);

        abort_unless(Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->download($path);
    }
}
