<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;   // ⬅️ add this
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountDecisionNotification extends Notification implements ShouldQueue   // ⬅️ implement it
{
    use Queueable;
    protected ?string $displayName;

    protected string $decision;
    protected ?string $note;
    protected ?string $actorName;
    protected array $meta;

    public function __construct(
    string $decision,
    ?string $note = null,
    ?string $actorName = null,
    array $meta = [],
    ?string $displayName = null,
) {
    $this->decision     = $decision;
    $this->note         = $note;
    $this->actorName    = $actorName;
    $this->meta         = $meta;
    $this->displayName  = $displayName;

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
    ?: ($notifiable->name ?? 'User');


        $subject = match ($this->decision) {
            'approved' => "Official Notice: Account Approval",
            'declined' => "Official Notice: Account Decision",
            default    => "Official Notice: Account Update",
        };

        $mail = (new MailMessage)
            ->subject("{$subject} — {$appName}")
            ->greeting("Dear {$displayName},");

        if ($this->decision === 'approved') {
            // Student placement details if provided
            if (!empty($this->meta['program_name']) || !empty($this->meta['section_name'])) {
                $mail->line('We are pleased to inform you that your student registration has been approved.')
                     ->line('Please find your details below:')
                     ->line(' Program     : '        . ($this->meta['program_name']    ?? '—'))
                     ->line(' Curriculum  : '     . ($this->meta['curriculum_name'] ?? '—'))
                     ->line(' Section     : '        . ($this->meta['section_name']    ?? '—'))
                     ->line(' Year & Term : '    . trim(($this->meta['year_label'] ?? '—').' — '.($this->meta['term_label'] ?? '—')));
            } else {
                // Staff or generic approval
                $mail->line('We are pleased to inform you that your account has been approved.');
            }

            if (!empty($this->actorName)) {
                $mail->line('Reviewed by: '.$this->actorName);
            }
            if (!empty($this->note)) {
                $mail->line('Remarks from the reviewer:')
                     ->line('"'.$this->note.'"');
            }

            $mail->action('Sign in to your account', url('/login'))
                 ->line('If you have any questions, you may reply to this email.');

        } elseif ($this->decision === 'declined') {
            $mail->line('We regret to inform you that your account request was not approved at this time.');

            if (!empty($this->actorName)) {
                $mail->line('Reviewed by: '.$this->actorName);
            }
            if (!empty($this->note)) {
                $mail->line('Remarks from the reviewer:')
                     ->line('"'.$this->note.'"');
            }

            $mail->line('If you require further clarification, kindly respond to this email.');

        } else {
            // Fallback for other decision types
            $titleDecision = ucfirst(str_replace('_', ' ', $this->decision));
            $mail->line("There has been an update regarding your account: {$titleDecision}.");
            if (!empty($this->note)) {
                $mail->line('Remarks:')
                     ->line('"'.$this->note.'"');
            }
        }

        return $mail->salutation("Sincerely,\n{$appName} Admissions / Registrar");
    }
}
