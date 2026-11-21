<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MenuRequest;
use App\Models\Menu;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MenuController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:active,inactive'],
            'type' => ['nullable', 'in:parent,child'],
        ]);

        $menus = Menu::query()
            ->with('parent:id,name')
            ->withCount('children')
            ->when($filters['q'] ?? null, function ($query, string $term): void {
                $query->where(function ($query) use ($term): void {
                    $query->where('name', 'like', "%{$term}%")
                        ->orWhere('url', 'like', "%{$term}%")
                        ->orWhereHas('parent', fn ($parentQuery) => $parentQuery->where('name', 'like', "%{$term}%"));
                });
            })
            ->when(isset($filters['status']), fn ($query) => $query->where('is_active', $filters['status'] === 'active'))
            ->when(($filters['type'] ?? null) === 'parent', fn ($query) => $query->whereNull('parent_id'))
            ->when(($filters['type'] ?? null) === 'child', fn ($query) => $query->whereNotNull('parent_id'))
            ->orderBy('sort_order')
            ->orderBy('parent_id')
            ->orderBy('id')
            ->paginate(10)
            ->withQueryString();

        return view('admin.menus.index', compact('menus'));
    }

    public function create(): View
    {
        return $this->formView(new Menu(['is_active' => true, 'sort_order' => 0]));
    }

    public function store(MenuRequest $request): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data): void {
            $menu = Menu::create($data);
            ActivityLogger::record('created', $menu, "Created menu '{$menu->name}'.");
        });

        return redirect()->route('admin.menus.index')->with('success', 'Menu created successfully.');
    }

    public function edit(Menu $menu): View
    {
        return $this->formView($menu);
    }

    public function update(MenuRequest $request, Menu $menu): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($menu, $data): void {
            $menu->update($data);
            ActivityLogger::record('updated', $menu, "Updated menu '{$menu->name}'.");
        });

        return redirect()->route('admin.menus.index')->with('success', 'Menu updated successfully.');
    }

    public function destroy(Menu $menu): RedirectResponse
    {
        if ($menu->children()->exists()) {
            return back()->with('error', 'Remove or move this menu’s child menus before deleting it.');
        }

        DB::transaction(function () use ($menu): void {
            ActivityLogger::record('deleted', $menu, "Deleted menu '{$menu->name}'.");
            $menu->delete();
        });

        return redirect()->route('admin.menus.index')->with('success', 'Menu deleted successfully.');
    }

    public function toggleStatus(Menu $menu): RedirectResponse
    {
        $isActive = ! $menu->is_active;

        DB::transaction(function () use ($menu, $isActive): void {
            $menu->update(['is_active' => $isActive]);
            ActivityLogger::record(
                'updated',
                $menu,
                ($isActive ? 'Activated' : 'Deactivated')." menu '{$menu->name}'.",
            );
        });

        return back()->with('success', "Menu {$menu->name} ".($isActive ? 'activated' : 'deactivated').'.');
    }

    private function formView(Menu $menu): View
    {
        $parentMenus = Menu::query()
            ->whereNull('parent_id')
            ->when($menu->exists, fn ($query) => $query->where('id', '!=', $menu->getKey()))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('admin.menus.form', [
            'menu' => $menu,
            'parentMenus' => $parentMenus,
            'canChooseParent' => ! $menu->exists || ! $menu->children()->exists(),
        ]);
    }
}
