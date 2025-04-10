<?php

namespace App\Services;
class FileServices
{
    public function store_file($file)
    {
        if ($file) {
            $image = $file;
            $filename = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME)
                . '_' . time() . '.' . $image->getClientOriginalExtension();

            $path = $image->storeAs('images', $filename, 'public');
            return $path;
        } else {
            return "Please upload a file";
        }
    }
}
