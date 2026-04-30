<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Submission extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 
        'task_title', 
        'file_url', 
        'grade'
    ];

    public function user()
{
    // Ini artinya: satu submission dimiliki oleh satu user
    return $this->belongsTo(User::class, 'user_id');
}
}