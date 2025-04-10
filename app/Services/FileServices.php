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

        $path = $image->move(public_path('images'), $filename); // <== stores directly in public/images
        return 'images/' . $filename;
    } else {
        return "Please upload a file";
    }
}

}
