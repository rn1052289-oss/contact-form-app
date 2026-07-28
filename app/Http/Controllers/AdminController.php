<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Contact;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function index(): View
    {
        return view('admin.index', [
            'categories' => Category::all(),
            'contacts' => Contact::with(['category', 'tags'])->paginate(),
        ]);
    }
}
