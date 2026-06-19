<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserApproval extends Model
{
    protected $fillable = ['user_id', 'acted_by', 'decision', 'note'];

    /**
     * The user who was approved/declined.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The admin/staff who performed the decision.
     */
    public function actor()
    {
        return $this->belongsTo(User::class, 'acted_by');
    }

    public function approve($actor, $note = null)
{
    $this->status = 'active'; // Update status to 'active' after approval
    $this->approved_by = $actor->id; // Admin who approved
    $this->approved_at = now(); // Set the approval timestamp
    $this->save();

    // Log the approval in the user_approvals table
    $this->approvals()->create([
        'acted_by' => $actor->id,
        'decision' => 'approved',
        'note' => $note ?? 'No note provided', // Optional note
    ]);
}

public function decline($actor, $note = null)
{
    $this->status = 'declined'; // Update status to 'declined' after rejection
    $this->declined_by = $actor->id; // Admin who rejected
    $this->declined_at = now(); // Set the rejection timestamp
    $this->save();

    // Log the rejection in the user_approvals table
    $this->approvals()->create([
        'acted_by' => $actor->id,
        'decision' => 'declined',
        'note' => $note ?? 'No note provided', // Optional note
    ]);
}

}
