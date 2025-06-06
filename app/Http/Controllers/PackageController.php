<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Package;
use Illuminate\Http\Request;

class PackageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (!auth()->user()->hasRole('Super Admin|Admin|Staff')) {
            return redirect('/')->with('error', 'Unauthorised to access this page.');
        }

        $packages = Package::paginate(10);

        return view('packages.index', compact(['packages']));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        if (!auth()->user()->hasRole('Super Admin|Admin')) {
            return redirect('/')->with('error', 'Unauthorised to create package!');
        }

        $courses = Course::all();
//        $package = new Package();
        return view('packages.create',compact(['courses']));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        $validated = $request->validate([
            'national_code' => ['required', 'string', 'regex:/^[A-Z]{3}$/', 'unique:packages,national_code,'],
            'title' => ['required', 'min:2', 'max:255', 'string',],
            'tga_status' => ['required', 'min:5', 'max:255', 'string',],
        ]);

        $package = Package::create($validated);

        foreach ($request->course_ids as $course_id) {
            $course = Course::whereId($course_id);
            $course->update(['package_id' => $package->id]);
        }

        return redirect()->route('packages.index')
            ->with('success', 'Package created successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(Package $package)
    {
        if (!auth()->user()->hasRole('Super Admin|Admin|Staff')) {
            return redirect('/')->with('error', 'Unauthorised to view package!');
        }

        $courses = $package->courses;

            if ($package) {
                return view('packages.show', compact(['package', 'courses']))
                    ->with('success', 'Package found');
            }

            return redirect(route('packages.index'))
                ->with('warning', 'Package not found');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Package $package)
    {
        if (!auth()->user()->hasRole('Super Admin|Admin')) {
            return redirect('/')->with('error', 'Unauthorised to edit package!');
        }

        $courses = Course::all();
        return view('packages.edit', compact('package', 'courses'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $package = Package::findOrFail($id);

        $validated = $request->validate([
            'national_code' => ['required', 'string', 'regex:/^[A-Z]{3}$/', 'unique:packages,national_code,' . $package->id],
            'title' => ['required', 'min:2', 'max:255', 'string',],
            'tga_status' => ['required', 'min:5', 'max:255', 'string',],
        ]);

        $oldCourses = Course::where('package_id', $package->id)->get();
        foreach ($oldCourses as $oldCourse) {
            $oldCourse->update(['package_id' => null]);
        }

        foreach ($request->course_ids as $course_id) {
            $course = Course::whereId($course_id);
            $course->update(['package_id' => $package->id]);
        }

        $package->update($validated);

        return redirect()->route('packages.index')
            ->with('success', 'Package updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Package $package)
    {
        if (!auth()->user()->hasRole('Super Admin|Admin')) {
            return redirect('/')->with('error', 'Unauthorised to delete package!');
        }

        $package->delete();
        return redirect()->route('packages.index')
            ->with('success', 'Package deleted successfully');
    }
}
