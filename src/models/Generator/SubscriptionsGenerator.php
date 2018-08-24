<?php

namespace Crm\SubscriptionsModule\Generator;

use Crm\ApplicationModule\Hermes\HermesMessage;
use Crm\PaymentsModule\Repository\PaymentGatewaysRepository;
use Crm\SubscriptionsModule\Events\NewSubscriptionEvent;
use Crm\SubscriptionsModule\Events\SubscriptionStartsEvent;
use Crm\SubscriptionsModule\Repository\SubscriptionPaymentsRepository;
use Crm\SubscriptionsModule\Repository\SubscriptionsRepository;
use DateTime;
use League\Event\Emitter;

class SubscriptionsGenerator
{
    private $subscriptionsRepository;

    private $paymentGatewaysRepository;

    private $subscriptionPaymentsRepository;

    private $emitter;

    private $hermesEmitter;

    public function __construct(
        SubscriptionsRepository $subscriptionsRepository,
        PaymentGatewaysRepository $paymentGatewaysRepository,
        SubscriptionPaymentsRepository $subscriptionPaymentsRepository,
        Emitter $emitter,
        \Tomaj\Hermes\Emitter $hermesEmitter
    ) {
        $this->subscriptionsRepository = $subscriptionsRepository;
        $this->paymentGatewaysRepository = $paymentGatewaysRepository;
        $this->subscriptionPaymentsRepository = $subscriptionPaymentsRepository;
        $this->emitter = $emitter;
        $this->hermesEmitter = $hermesEmitter;
    }

    public function generate(SubscriptionsParams $params, $count)
    {
        $paymentGateway = $this->paymentGatewaysRepository->findBy('code', 'custom');

        $payment = $params->getPayment();

        for ($i = 0; $i < $count; $i++) {
            $subscription = $this->subscriptionsRepository->add(
                $params->getSubscriptionType(),
                $paymentGateway ? $paymentGateway->is_recurrent : false,
                $params->getUser(),
                $params->getType(),
                $params->getStartTime(),
                $params->getEndTime()
            );

            if ($payment) {
                $this->subscriptionPaymentsRepository->add($subscription, $payment);
            }

            // tento emiter by tu nemusel byt, idealne by bolo keby sa to robilo v add metode asi
            // treba zrefaktorovat aj v paymentprocessore
            $this->emitter->emit(new NewSubscriptionEvent($subscription));
            $this->hermesEmitter->emit(new HermesMessage('new-subscription', [
                'subscription_id' => $subscription->id,
            ]));

            if ($subscription->start_time <= new DateTime() and $subscription->end_time > new DateTime()) {
                $this->subscriptionsRepository->update($subscription, ['internal_status' => SubscriptionsRepository::INTERNAL_STATUS_ACTIVE]);
                $this->emitter->emit(new SubscriptionStartsEvent($subscription));
            } else {
                $this->subscriptionsRepository->update($subscription, ['internal_status' => SubscriptionsRepository::INTERNAL_STATUS_BEFORE_START]);
            }
        }
    }
}
