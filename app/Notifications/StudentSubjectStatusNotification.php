<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StudentSubjectStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected array $meta;
    protected string $action; // enrolled | dropped
    protected ?string $displayName;

    public function __construct(string $action, array $meta = [], ?string $displayName = null)
    {
        $this->action      = $action; // 'enrolled' or 'dropped'
        $this->meta        = $meta;
        $this->displayName = $displayName;

        // Match approval notification behavior
        $this->afterCommit = true;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $appName = config('app.name', 'Granby Colleges');

        $name = $this->displayName
            ?? trim(($notifiable->first_name ?? '').' '.($notifiable->last_name ?? ''))
            ?: ($notifiable->name ?? 'Student');

        $actionText = $this->action === 'enrolled'
            ? 'Subject Enrollment Confirmation'
            : 'Subject Drop Notification';

        $mail = (new MailMessage)
            ->subject("Official Notification: {$actionText} — {$appName}")
            ->greeting("Dear {$name},")

            ->line(
                $this->action === 'enrolled'
                    ? 'This is to formally inform you that you have been officially enrolled in the subject detailed below.'
                    : 'This is to formally inform you that you have been officially removed from the subject detailed below.'
            );

        // Academic details
        if (!empty($this->meta['subject_code'])) {
            $mail->line('Subject Code: '.$this->meta['subject_code']);
        }

        if (!empty($this->meta['subject_name'])) {
            $mail->line('Subject Title: '.$this->meta['subject_name']);
        }

        if (!empty($this->meta['term'])) {
            $mail->line('Academic Term: '.$this->meta['term']);
        }

        if (!empty($this->meta['school_year'])) {
            $mail->line('Academic Year: '.$this->meta['school_year']);
        }

        $mail
            ->line(
                'Please log in to the student portal to review your updated class schedule and academic records.'
            )
            ->action('View Student Schedule', url('/student/schedule'))
            ->line(
                'If you believe this update has been made in error or require further clarification, kindly contact your academic department or the Office of the Registrar.'
            );

        return $mail->salutation(
            "Respectfully,\n{$appName}\nOffice of the Registrar"
        );
    }
}
