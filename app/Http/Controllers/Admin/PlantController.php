<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\PlantRequest;
use App\Models\Plant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;

/**
 * @extends CmsController<Plant>
 */
class PlantController extends CmsController
{
    protected function modelClass(): string
    {
        return Plant::class;
    }

    protected function routeName(): string
    {
        return 'admin.plants';
    }

    protected function parameter(): string
    {
        return 'plant';
    }

    protected function title(): string
    {
        return __('Nursery plants');
    }

    protected function singular(): string
    {
        return __('Plant');
    }

    protected function listQuery(): Builder
    {
        return Plant::query()->orderBy('sort')->orderBy('name');
    }

    protected function row(Model $record): array
    {
        if (! $record instanceof Plant) {
            throw new \InvalidArgumentException('Expected a plant.');
        }

        return [
            'title' => (string) $record->name,
            'meta' => rupees($record->price).' · '.__(':count in stock', ['count' => digits($record->stock)]),
            'published' => $record->isPublished(),
        ];
    }

    public function store(PlantRequest $request): RedirectResponse
    {
        $plant = Plant::query()->create([...$request->plantData(), ...$this->statusFromButton($request)]);
        $this->savePhoto($request, $plant);
        $this->saveImages($request, $plant);

        return $this->saved($request, 'admin.plants.index', __('Plant'));
    }

    public function update(PlantRequest $request, Plant $plant): RedirectResponse
    {
        $plant->update([...$request->plantData(), ...$this->statusFromButton($request, $plant)]);
        $this->savePhoto($request, $plant);
        $this->saveImages($request, $plant);

        return $this->saved($request, 'admin.plants.index', __('Plant'));
    }

    /**
     * More photos for the details panel: add the uploaded ones, remove the ticked ones.
     */
    private function saveImages(PlantRequest $request, Plant $plant): void
    {
        $remove = array_map('intval', (array) $request->input('remove_images', []));
        $plant->getMedia('images')->whereIn('id', $remove)->each->delete();

        foreach ((array) $request->file('images_upload', []) as $file) {
            $plant->addMedia($file)->toMediaCollection('images');
        }
    }
}
