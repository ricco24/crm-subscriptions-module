<?php

namespace Crm\SubscriptionsModule\Components;

use Crm\ApplicationModule\Widget\BaseWidget;
use Crm\ApplicationModule\Widget\WidgetManager;
use Crm\SubscriptionsModule\Repository\SubscriptionsRepository;
use Nette\Utils\DateTime;

/**
 * This widget fetches subscriptions created in this month
 * and last month and renders simple line with both lines.
 *
 * @package Crm\SubscriptionsModule\Components
 */
class MonthSubscriptionsStatWidget extends BaseWidget
{
    private $templateName = 'month_subscriptions_stat_widget.latte';

    private $subscriptionsRepository;

    public function __construct(
        WidgetManager $widgetManager,
        SubscriptionsRepository $subscriptionsRepository
    ) {
        parent::__construct($widgetManager);
        $this->subscriptionsRepository = $subscriptionsRepository;
    }

    public function identifier()
    {
        return 'monthsubscriptionsstatwidget';
    }

    public function render()
    {
        $this->template->thisMonthSubscriptions = $this->subscriptionsRepository->subscriptionsCreatedBetween(
            DateTime::from(date('Y-m')),
            new DateTime()
        )->count('*');
        $this->template->lastMonthSubscriptions = $this->subscriptionsRepository->subscriptionsCreatedBetween(
            DateTime::from('first day of -1 month 00:00'),
            DateTime::from(date('Y-m'))
        )->count('*');
        $this->template->setFile(__DIR__ . DIRECTORY_SEPARATOR . $this->templateName);
        $this->template->render();
    }
}
