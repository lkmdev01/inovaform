<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FormBuilderController extends Controller
{
    public function index(Request $request): RedirectResponse
    {
        return redirect()
            ->route('funnels.index')
            ->with('status', 'form-builder-deprecated');
    }

    public function store(Request $request): RedirectResponse
    {
        return redirect()
            ->route('funnels.index')
            ->with('status', 'form-builder-deprecated');
    }
}
