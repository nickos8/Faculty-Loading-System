<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentCurriculumSubject extends Model
{
    protected $fillable = [
        'student_academic_id',
        'curriculum_term_subject_id',
        'class_offering_id',
        'evaluated_by',
        'status',
        'evaluated_at',
        'remarks',
    ];

    public function academic()
    {
        return $this->belongsTo(StudentAcademic::class, 'student_academic_id');
    }

    public function curriculumTermSubject()
    {
        return $this->belongsTo(CurriculumTermSubject::class);
    }

    public function classOffering()
    {
        return $this->belongsTo(ClassOffering::class);
    }

    public function evaluator()
    {
        return $this->belongsTo(User::class, 'evaluated_by');
    }

    /**
     * When a new curriculum_term_subject is added,
     * create student_curriculum_subjects for all students under that curriculum.
     */
    public static function syncForNewCurriculumTermSubject(CurriculumTermSubject $cts): void
    {
        $curriculumId = $cts->term->curriculum_id; // assumes relation 'term' on CurriculumTermSubject

        // Process in chunks so it scales
        StudentAcademic::where('curriculum_id', $curriculumId)
            ->chunkById(100, function ($academics) use ($cts) {
                foreach ($academics as $academic) {
                    // Avoid duplicates if this was already synced
                    self::firstOrCreate(
                        [
                            'student_academic_id'        => $academic->id,
                            'curriculum_term_subject_id' => $cts->id,
                        ],
                        [
                            'status' => 'not_taken',
                        ]
                    );
                }
            });
    }

     public function subject()
    {
        return $this->hasOneThrough(
            Subject::class,
            CurriculumTermSubject::class,
            'id',                 // curriculum_term_subjects.id
            'id',                 // subjects.id
            'curriculum_term_subject_id',
            'subject_id'
        );
    }
}
