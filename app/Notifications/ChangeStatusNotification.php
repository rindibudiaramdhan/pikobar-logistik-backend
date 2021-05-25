<?php

namespace App\Notifications;

use App\Channels\SmsChannel;
use App\Channels\WhatsappChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ChangeStatusNotification extends Notification
{
    use Queueable;

    public $link;
    public $id;
    public $phase;
    // Message Component Variables
    public $header = '*Info Logistik PIKOBAR*';
    public $action = 'Verifikasi Administrasi';
    public $sendTo = 'PIC Surat';
    public $state = 'telah masuk surat permohonan logistik baru';
    public $position = '';
    public $nextStep = 'verifikasi administrasi dokumen';
    public $nextAction = 'diverifikasi';
    public $content;
    public $lastMessage = 'Surat permohonan tersebut dapat diakses dengan menggunakan aplikasi permohonan logistik. Berikut ini link permohonan yang perlu ' . $this->nextAction .': ' . $this->link;


    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($param)
    {
        $this->id = $param['id'];
        $this->phase = $param['phase'];
        $this->link = $param['url'] . '/alat-kesehatan/detail/' . $this->id;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return [WhatsappChannel::class];
    }

    public function toSms($notifiable)
    {
        $message = $this->setMessage();
        return $message;
    }

    public function toWhatsapp($notifiable)
    {
        $message = $this->setMessage();
        return $message;
    }

    public function setMessage()
    {
        switch ($this->phase) {
            case 'rekomendasi':
                $this->action = 'Rekomendasi Salur';
                $this->sendTo = 'PIC Rekomendasi Salur';
                $this->state = 'surat permohonan logistik';
                $this->position = 'telah berada pada tahapan rekomendasi salur.';
                $this->nextStep  = 'rekomendasi salur';
                $this->nextAction  = 'rekomendasi salur';
                $this->lastMessage = 'Adapun untuk melakukan '. $this->nextStep .' dapat diakses dengan menggunakan aplikasi permohonan logistik. Berikut ini link permohonan yang perlu dilakukan '. $this->nextAction .': ' . $this->link;
                $this->content = 'Saat ini pada aplikasi permohonan logistik '. $this->state .' dengan kode: ' . $this->id .' '. $this->position .' Mohon ditindaklanjuti untuk melakukan '. $this->nextStep .' terhadap permohonan tersebut. '. $this->lastMessage;
                break;
            case 'realisasi':
                $this->action = 'Realisasi Salur';
                $this->sendTo = 'PIC Realisasi Salur';
                $this->state = 'surat permohonan logistik';
                $this->position = 'telah berada pada tahapan realisasi salur';
                $this->nextStep = 'realisasi salur';
                $this->nextAction = 'realisasi salur';
                $this->lastMessage = 'Adapun untuk melakukan '. $this->nextStep .' dapat diakses dengan menggunakan aplikasi permohonan logistik. Berikut ini link permohonan yang perlu dilakukan '. $this->nextAction .': ' . $this->link;
                $this->content = 'Saat ini pada aplikasi permohonan logistik '. $this->state .' dengan kode: ' . $this->id .' '. $this->position .' Mohon ditindaklanjuti untuk melakukan '. $this->nextStep .' terhadap permohonan tersebut. '. $this->lastMessage;
                break;
        }

        $this->content = 'Saat ini pada aplikasi permohonan logistik '. $this->state .' dengan kode: ' . $this->id .' '. $this->position .' Mohon ditindaklanjuti untuk melakukan '. $this->nextStep .' terhadap permohonan tersebut. '. $this->lastMessage;

        return $this->header .
                '*Butuh ' . $this->action . ', kode : ' . $this->id .'*

                Kepada Yth.
                ' . $this->sendTo . ' '
                . $this->content;
    }

    /**
     * Get the array representation of the notification.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
