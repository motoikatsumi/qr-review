<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Store;
use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QrCodeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function show(Store $store)
    {
        $reviewUrl = url('/review/' . $store->slug);

        $qrCode = QrCode::format('svg')
            ->size(400)
            ->margin(2)
            ->generate($reviewUrl);

        return view('admin.qrcode.show', [
            'store' => $store,
            'qrCode' => $qrCode,
            'reviewUrl' => $reviewUrl,
        ]);
    }

    public function download(Store $store)
    {
        $reviewUrl = request()->getSchemeAndHttpHost() . '/review/' . $store->slug;

        $qrCode = QrCode::format('png')
            ->size(600)
            ->margin(2)
            ->generate($reviewUrl);

        $filename = 'qrcode_' . $store->slug . '.png';

        return response($qrCode)
            ->header('Content-Type', 'image/png')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }
}
