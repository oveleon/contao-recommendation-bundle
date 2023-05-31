<?php

declare(strict_types=1);

namespace Oveleon\ContaoRecommendationBundle\Security;

final class ContaoRecommendationPermissions
{
    public const USER_CAN_EDIT_ARCHIVE = 'contao_user.recommendations';
    public const USER_CAN_CREATE_ARCHIVES = 'contao_user.recommendationp.create';
    public const USER_CAN_DELETE_ARCHIVES = 'contao_user.recommendationp.delete';
}
