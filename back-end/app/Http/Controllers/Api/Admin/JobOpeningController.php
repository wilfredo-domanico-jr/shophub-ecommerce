<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\JobOpening;
use Illuminate\Http\Request;

class JobOpeningController extends Controller
{
    public function index()
    {
        return JobOpening::latest()->get();
    }

    public function store(Request $request)
    {
        $opening = JobOpening::create($this->validated($request));

        return response()->json($opening, 201);
    }

    public function update(Request $request, JobOpening $career)
    {
        $career->update($this->validated($request));

        return $career;
    }

    public function destroy(JobOpening $career)
    {
        $career->delete();

        return response()->json(['message' => 'Job opening removed']);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'department' => ['required', 'string', 'max:255'],
            'location' => ['required', 'string', 'max:255'],
            'employment_type' => ['required', 'string', 'max:50'],
            'description' => ['required', 'string', 'max:2000'],
            'is_active' => ['boolean'],
        ]);
    }
}
