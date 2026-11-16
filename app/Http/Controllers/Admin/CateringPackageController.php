<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\CateringPackageRequest;
use App\Models\CateringPackage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;

/**
 * @extends CmsController<CateringPackage>
 */
class CateringPackageController extends CmsController
{
    protected function modelClass(): string
    {
        return CateringPackage::class;
    }

    protected function routeName(): string
    {
        return 'admin.packages';
    }

    protected function parameter(): string
    {
        return 'package';
    }

    protected function title(): string
    {
        return __('Serving packages');
    }

    protected function singular(): string
    {
        return __('Package');
    }

    protected function listQuery(): Builder
    {
        return CateringPackage::query()->orderBy('sort');
    }

    protected function row(Model $record): array
    {
        if (! $record instanceof CateringPackage) {
            throw new \InvalidArgumentException('Expected a package.');
        }

        return [
            'title' => (string) $record->name,
            'meta' => rupees($record->rate).' '.__('a guest'),
            'published' => $record->isPublished(),
        ];
    }

    public function store(CateringPackageRequest $request): RedirectResponse
    {
        CateringPackage::query()->create([...$request->packageData(), ...$this->statusFromButton($request)]);

        return $this->saved($request, 'admin.packages.index', __('Package'));
    }

    public function update(CateringPackageRequest $request, CateringPackage $package): RedirectResponse
    {
        $package->update([...$request->packageData(), ...$this->statusFromButton($request, $package)]);

        return $this->saved($request, 'admin.packages.index', __('Package'));
    }
}
