<?php

/*
 * This file is part of Oveleon Recommendation Bundle.
 *
 * (c) https://www.oveleon.de/
 */

namespace Oveleon\ContaoRecommendationBundle\Model;

use Contao\Model;
use Contao\Model\Collection;

/**
 * Reads and writes recommendation archives
 *
 * @property integer $id
 * @property integer $tstamp
 * @property string  $title
 * @property integer $jumpTo
 * @property boolean $protected
 * @property string  $groups
 *
 * @method static RecommendationArchiveModel|null findById($id, array $opt=array())
 * @method static RecommendationArchiveModel|null findByPk($id, array $opt=array())
 * @method static RecommendationArchiveModel|null findByIdOrAlias($val, array $opt=array())
 * @method static RecommendationArchiveModel|null findOneBy($col, $val, array $opt=array())
 * @method static RecommendationArchiveModel|null findOneByTstamp($val, array $opt=array())
 * @method static RecommendationArchiveModel|null findOneByTitle($val, array $opt=array())
 * @method static RecommendationArchiveModel|null findOneByJumpTo($val, array $opt=array())
 * @method static RecommendationArchiveModel|null findOneByProtected($val, array $opt=array())
 * @method static RecommendationArchiveModel|null findOneByGroups($val, array $opt=array())
 *
 * @method static Collection|RecommendationArchiveModel[]|RecommendationArchiveModel|null findByTstamp($val, array $opt=array())
 * @method static Collection|RecommendationArchiveModel[]|RecommendationArchiveModel|null findByTitle($val, array $opt=array())
 * @method static Collection|RecommendationArchiveModel[]|RecommendationArchiveModel|null findByJumpTo($val, array $opt=array())
 * @method static Collection|RecommendationArchiveModel[]|RecommendationArchiveModel|null findByProtected($val, array $opt=array())
 * @method static Collection|RecommendationArchiveModel[]|RecommendationArchiveModel|null findByGroups($val, array $opt=array())
 * @method static Collection|RecommendationArchiveModel[]|RecommendationArchiveModel|null findMultipleByIds($val, array $opt=array())
 * @method static Collection|RecommendationArchiveModel[]|RecommendationArchiveModel|null findBy($col, $val, array $opt=array())
 * @method static Collection|RecommendationArchiveModel[]|RecommendationArchiveModel|null findAll(array $opt=array())
 *
 * @method static integer countById($id, array $opt=array())
 * @method static integer countByTstamp($val, array $opt=array())
 * @method static integer countByTitle($val, array $opt=array())
 * @method static integer countByJumpTo($val, array $opt=array())
 * @method static integer countByProtected($val, array $opt=array())
 * @method static integer countByGroups($val, array $opt=array())
 */
class RecommendationArchiveModel extends Model
{

    /**
     * Table name
     * @var string
     */
    protected static $strTable = 'tl_recommendation_archive';
}

class_alias(RecommendationArchiveModel::class, 'RecommendationArchiveModel');
