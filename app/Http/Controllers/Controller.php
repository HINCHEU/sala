<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;

abstract class Controller
{
    /**
     * Delete a record unless something still points at it. Foreign keys restrict deletes, so a subject
     * used in a curriculum (or, later, in scores) survives and the user gets a plain message instead of a 500.
     */
    protected function destroyUnlessUsed(Model $model): RedirectResponse
    {
        try {
            $model->deleteOrFail();
        } catch (QueryException $e) {
            if ($e->getCode() !== '23000') {
                throw $e;
            }

            return back()->with('error', __('academic.in_use'));
        }

        return back()->with('success', __('academic.deleted'));
    }
}
