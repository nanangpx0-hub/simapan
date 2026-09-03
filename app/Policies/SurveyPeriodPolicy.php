<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SurveyPeriod;
use App\Models\User;

class SurveyPeriodPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('master.survey_period.view');
    }

    public function view(User $user, SurveyPeriod $surveyPeriod): bool
    {
        return $user->can('master.survey_period.view');
    }

    public function create(User $user): bool
    {
        return $user->can('master.survey_period.manage');
    }

    public function update(User $user, SurveyPeriod $surveyPeriod): bool
    {
        return $user->can('master.survey_period.manage');
    }
}
