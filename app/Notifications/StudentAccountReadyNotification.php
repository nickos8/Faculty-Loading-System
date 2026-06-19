<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StudentAccountReadyNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected ?string $displayName;
    protected array $meta;

    public function __construct(array $meta = [], ?string $displayName = null)
    {
        $this->meta        = $meta;
        $this->displayName = $displayName;

        // Match existing notification behavior
        $this->afterCommit = true;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $appName = config('app.name', 'Granby Colleges');

        $displayName = $this->displayName
            ?? trim(($notifiable->first_name ?? '').' '.($notifiable->last_name ?? ''))
            ?: ($notifiable->name ?? 'Student');

        $subject = 'Official Notification: Student Account Activation';

        $mail = (new MailMessage)
            ->subject("{$subject} — {$appName}")
            ->greeting("Dear {$displayName},")

            ->line(
                'We are pleased to inform you that your official student account has been successfully created and is now active in the '.$appName.' system.'
            );

        // Optional student details (formal presentation)
        if (!empty($this->meta['school_id'])) {
            $mail->line('Student Number: '.$this->meta['school_id']);
        }
        if (!empty($this->meta['program_name'])) {
            $mail->line('Program: '.$this->meta['program_name']);
        }
        if (!empty($this->meta['section_name'])) {
            $mail->line('Section: '.$this->meta['section_name']);
        }

        $mail
            ->line(
                'You may now access your account to view your academic information, class schedules, and other student-related services.'
            )
            ->action('Access Student Portal', url('/login'))
            ->line(
                'Should you require assistance or have any questions regarding your account, please contact the appropriate office or reply to this email for further guidance.'
            );

        return $mail->salutation(
            "Respectfully,\n{$appName}\nOffice of the Registrar"
        );
    }
}
