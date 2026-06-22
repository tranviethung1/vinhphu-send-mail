<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SalaryMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $employeeName;
    public string $salaryFileName;
    public ?string $month;
    public string $pdfContent;
    public string $pdfFileName;
    public string $type;
    public ?string $description;
    public ?string $templateName;

    /**
     * Create a new message instance.
     */
    public function __construct(
        string $employeeName,
        string $salaryFileName,
        ?string $month,
        string $pdfContent,
        string $pdfFileName,
        string $type = 'salary',
        ?string $description = null,
        ?string $templateName = null
    ) {
        $this->employeeName = $employeeName;
        $this->salaryFileName = $salaryFileName;
        $this->month = $month;
        $this->pdfContent = $pdfContent;
        $this->pdfFileName = $pdfFileName;
        $this->type = $type;
        $this->description = $description;
        $this->templateName = $templateName;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = ($this->type === 'bonus') 
            ? ($this->templateName ?: 'Phiếu thưởng') 
            : 'Phiếu lương';

        if ($this->month && $this->type !== 'bonus') {
            $subject .= ' tháng ' . $this->month;
        }

        $subject .= ' - ' . $this->employeeName;

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $view = ($this->type === 'bonus') ? 'emails.thuong' : 'emails.salary';
        
        return new Content(
            view: $view,
            with: [
                'employeeName' => $this->employeeName,
                'salaryFileName' => $this->salaryFileName,
                'month' => $this->month,
                'description' => $this->description,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->pdfContent, $this->pdfFileName)
                ->withMime('application/pdf'),
        ];
    }
}
