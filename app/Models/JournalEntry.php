<?php

namespace App\Models;

use LogicException;

class JournalEntry extends CampusModel
{
    protected $casts = ['entry_date' => 'date'];

    protected static function booted(): void
    {
        static::updating(fn (JournalEntry $entry) => throw new LogicException('Jurnal yang sudah diposting tidak dapat diubah.'));
        static::deleting(fn (JournalEntry $entry) => throw new LogicException('Jurnal yang sudah diposting tidak dapat dihapus.'));
    }

    public function university()
    {
        return $this->belongsTo(University::class);
    }

    public function lines()
    {
        return $this->hasMany(JournalLine::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
