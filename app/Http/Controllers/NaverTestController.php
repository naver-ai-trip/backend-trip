<?php

namespace App\Http\Controllers;

use App\Services\Naver\ClovaOcrService;
use App\Services\Naver\ClovaSpeechService;
use App\Services\Naver\GreenEyeService;
use App\Services\Naver\NaverMapsService;
use App\Services\Naver\PapagoService;
use Illuminate\Http\Request;

class NaverTestController extends Controller
{
    public function index()
    {
        return view('naver-test');
    }

    public function testMaps(Request $request, NaverMapsService $mapsService)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'radius' => 'required|integer',
            'query' => 'nullable|string',
        ]);

        try {
            $result = $mapsService->searchNearbyPlaces(
                $request->latitude,
                $request->longitude,
                $request->radius,
                $request->query ?? 'restaurant'
            );

            return response()->json([
                'success' => true,
                'service' => 'maps',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function testPapago(Request $request, PapagoService $papagoService)
    {
        $request->validate([
            'text' => 'required|string',
            'source_lang' => 'nullable|string',
            'target_lang' => 'required|string',
        ]);

        try {
            $result = $papagoService->translate(
                $request->text,
                $request->target_lang,
                $request->source_lang === 'auto' ? null : $request->source_lang
            );

            return response()->json([
                'success' => true,
                'service' => 'papago',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function testPapagoImage(Request $request, PapagoService $papagoService)
    {
        $request->validate([
            'image' => 'required|image|max:10240',
            'target_lang' => 'required|string',
        ]);

        try {
            $image = $request->file('image');
            $result = $papagoService->translateImage(
                $image->getRealPath(),
                $request->target_lang
            );

            return response()->json([
                'success' => true,
                'service' => 'papago-image',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function testGreenEye(Request $request, GreenEyeService $greenEyeService)
    {
        $request->validate([
            'image' => 'required|image|max:10240',
        ]);

        try {
            $image = $request->file('image');
            $path = $image->store('temp', 'public');
            $url = asset('storage/' . $path);

            $result = $greenEyeService->checkImageSafety($url);

            return response()->json([
                'success' => true,
                'service' => 'greeneye',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function testOcr(Request $request, ClovaOcrService $ocrService)
    {
        $request->validate([
            'image' => 'required|image|max:10240',
        ]);

        try {
            $image = $request->file('image');
            $result = $ocrService->extractText($image->getRealPath());

            return response()->json([
                'success' => true,
                'service' => 'ocr',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function testSpeech(Request $request, ClovaSpeechService $speechService)
    {
        $request->validate([
            'audio' => 'required|file|mimes:mp3,wav,m4a|max:20480',
        ]);

        try {
            $audio = $request->file('audio');
            $result = $speechService->transcribe($audio->getRealPath());

            return response()->json([
                'success' => true,
                'service' => 'speech',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
