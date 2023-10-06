<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Machines extends Model
{
    protected $guarded = ['id'];
    protected $appends = ['image_url'];

    public function getImageUrlAttribute()
    {
        if (!empty($this->image)) {
            $image_url = asset('/uploads/machines_images/' . rawurlencode($this->image));
        } else {
            $image_url = asset('/img/default.png');
        }
        return $image_url;
    }

    public function getImagePathAttribute()
    {
        if (!empty($this->image)) {
            $image_path = public_path('uploads/machines_images') . '/' . config('constants.machine_img_path') . '/' . $this->image;
        } else {
            $image_path = null;
        }
        return $image_path;
    }
}
