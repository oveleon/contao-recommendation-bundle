<?php

/*
 * This file is part of Oveleon Recommendation Bundle.
 *
 * (c) https://www.oveleon.de/
 */

namespace Oveleon\ContaoRecommendationBundle;

/**
 * Reads and writes recommendations
 *
 * @property integer $id
 * @property integer $pid
 * @property integer $tstamp
 * @property string	 $author
 * @property string	 $email
 * @property integer $location
 * @property string  $alias
 * @property integer $date
 * @property integer $time
 * @property string  $text
 * @property string  $imageUrl
 * @property integer $rating
 * @property string  $cssClass
 * @property boolean $featured
 * @property boolean $verified
 * @property boolean $published
 * @property string  $start
 * @property string  $stop
 *
 * @method static RecommendationModel|null findById($id, array $opt=array())
 * @method static RecommendationModel|null findByPk($id, array $opt=array())
 * @method static RecommendationModel|null findByIdOrAlias($val, array $opt=array())
 * @method static RecommendationModel|null findOneBy($col, $val, array $opt=array())
 * @method static RecommendationModel|null findOneByPid($val, array $opt=array())
 * @method static RecommendationModel|null findOneByTstamp($val, array $opt=array())
 * @method static RecommendationModel|null findOneByAuthor($val, array $opt=array())
 * @method static RecommendationModel|null findOneByEmail($val, array $opt=array())
 * @method static RecommendationModel|null findOneByAlias($val, array $opt=array())
 * @method static RecommendationModel|null findOneByDate($val, array $opt=array())
 * @method static RecommendationModel|null findOneByTime($val, array $opt=array())
 * @method static RecommendationModel|null findOneByText($val, array $opt=array())
 * @method static RecommendationModel|null findOneByImageUrl($val, array $opt=array())
 * @method static RecommendationModel|null findOneByRating($val, array $opt=array())
 * @method static RecommendationModel|null findOneByCssClass($val, array $opt=array())
 * @method static RecommendationModel|null findOneByFeatured($val, array $opt=array())
 * @method static RecommendationModel|null findOneByVerified($val, array $opt=array())
 * @method static RecommendationModel|null findOneByPublished($val, array $opt=array())
 * @method static RecommendationModel|null findOneByStart($val, array $opt=array())
 * @method static RecommendationModel|null findOneByStop($val, array $opt=array())
 *
 * @method static \Model\Collection|RecommendationModel[]|RecommendationModel|null findByPid($val, array $opt=array())
 * @method static \Model\Collection|RecommendationModel[]|RecommendationModel|null findByTstamp($val, array $opt=array())
 * @method static \Model\Collection|RecommendationModel[]|RecommendationModel|null findByAuthor($val, array $opt=array())
 * @method static \Model\Collection|RecommendationModel[]|RecommendationModel|null findByEmail($val, array $opt=array())
 * @method static \Model\Collection|RecommendationModel[]|RecommendationModel|null findByAlias($val, array $opt=array())
 * @method static \Model\Collection|RecommendationModel[]|RecommendationModel|null findByDate($val, array $opt=array())
 * @method static \Model\Collection|RecommendationModel[]|RecommendationModel|null findByTime($val, array $opt=array())
 * @method static \Model\Collection|RecommendationModel[]|RecommendationModel|null findByText($val, array $opt=array())
 * @method static \Model\Collection|RecommendationModel[]|RecommendationModel|null findByImageUrl($val, array $opt=array())
 * @method static \Model\Collection|RecommendationModel[]|RecommendationModel|null findByRating($val, array $opt=array())
 * @method static \Model\Collection|RecommendationModel[]|RecommendationModel|null findByCssClass($val, array $opt=array())
 * @method static \Model\Collection|RecommendationModel[]|RecommendationModel|null findByFeatured($val, array $opt=array())
 * @method static \Model\Collection|RecommendationModel[]|RecommendationModel|null findByVerified($val, array $opt=array())
 * @method static \Model\Collection|RecommendationModel[]|RecommendationModel|null findByPublished($val, array $opt=array())
 * @method static \Model\Collection|RecommendationModel[]|RecommendationModel|null findByStart($val, array $opt=array())
 * @method static \Model\Collection|RecommendationModel[]|RecommendationModel|null findByStop($val, array $opt=array())
 * @method static \Model\Collection|RecommendationModel[]|RecommendationModel|null findMultipleByIds($val, array $opt=array())
 * @method static \Model\Collection|RecommendationModel[]|RecommendationModel|null findBy($col, $val, array $opt=array())
 * @method static \Model\Collection|RecommendationModel[]|RecommendationModel|null findAll(array $opt=array())
 *
 * @method static integer countById($id, array $opt=array())
 * @method static integer countByPid($val, array $opt=array())
 * @method static integer countByTstamp($val, array $opt=array())
 * @method static integer countByAuthor($val, array $opt=array())
 * @method static integer countByEmail($val, array $opt=array())
 * @method static integer countByAlias($val, array $opt=array())
 * @method static integer countByDate($val, array $opt=array())
 * @method static integer countByTime($val, array $opt=array())
 * @method static integer countByText($val, array $opt=array())
 * @method static integer countByImageUrl($val, array $opt=array())
 * @method static integer countByRating($val, array $opt=array())
 * @method static integer countByCssClass($val, array $opt=array())
 * @method static integer countByFeatured($val, array $opt=array())
 * @method static integer countByVerified($val, array $opt=array())
 * @method static integer countByPublished($val, array $opt=array())
 * @method static integer countByStart($val, array $opt=array())
 * @method static integer countByStop($val, array $opt=array())
 *
 * @author Fabian Ekert <fabian@oveleon.de>
 */
class RecommendationModel extends \Model
{

	/**
	 * Table name
	 * @var string
	 */
	protected static $strTable = 'tl_recommendation';

    /**
     * Find a published recommendation from one or more recommendation archives by its ID or alias
     *
     * @param mixed $varId      The numeric ID or alias name
     * @param array $arrPids    An array of parent IDs
     * @param array $arrOptions An optional options array
     *
     * @return RecommendationModel|null The model or null if there are no recommendations
     */
    public static function findPublishedByParentAndIdOrAlias($varId, $arrPids, array $arrOptions=array())
    {
        if (empty($arrPids) || !\is_array($arrPids))
        {
            return null;
        }

        $t = static::$strTable;
        $arrColumns = !is_numeric($varId) ? array("$t.alias=?") : array("$t.id=?");
        $arrColumns[] = "$t.pid IN(" . implode(',', array_map('\intval', $arrPids)) . ") AND $t.verified='1'";

        if (!static::isPreviewMode($arrOptions))
        {
            $time = \Date::floorToMinute();
            $arrColumns[] = "($t.start='' OR $t.start<='$time') AND ($t.stop='' OR $t.stop>'" . ($time + 60) . "') AND $t.published='1'";
        }

        return static::findOneBy($arrColumns, $varId, $arrOptions);
    }

    /**
     * Find published recommendations by their parent ID
     *
     * @param array   $arrPids     An array of recommendation archive IDs
     * @param boolean $blnFeatured If true, return only featured recommendations, if false, return only unfeatured recommendations
     * @param integer $intLimit    An optional limit
     * @param integer $intOffset   An optional offset
     * @param array   $arrOptions  An optional options array
     *
     * @return }Model\Collection|RecommendationModel[]|RecommendationModel|null A collection of models or null if there are no recommendations
     */
    public static function findPublishedByPids($arrPids, $blnFeatured=null, $intLimit=0, $intOffset=0, array $arrOptions=array())
    {
        if (empty($arrPids) || !\is_array($arrPids))
        {
            return null;
        }

        $t = static::$strTable;
        $arrColumns = array("$t.pid IN(" . implode(',', array_map('\intval', $arrPids)) . ") AND $t.verified='1'");

        if ($blnFeatured === true)
        {
            $arrColumns[] = "$t.featured='1'";
        }
        elseif ($blnFeatured === false)
        {
            $arrColumns[] = "$t.featured=''";
        }

        if (!static::isPreviewMode($arrOptions))
        {
            $time = \Date::floorToMinute();
            $arrColumns[] = "($t.start='' OR $t.start<='$time') AND ($t.stop='' OR $t.stop>'" . ($time + 60) . "') AND $t.published='1'";
        }

        if (!isset($arrOptions['order']))
        {
            $arrOptions['order']  = "$t.date DESC";
        }

        $arrOptions['limit']  = $intLimit;
        $arrOptions['offset'] = $intOffset;

        return static::findBy($arrColumns, null, $arrOptions);
    }

    /**
     * Count published recommendations by their parent ID
     *
     * @param array   $arrPids     An array of recommendation archive IDs
     * @param boolean $blnFeatured If true, return only featured recommendations, if false, return only unfeatured recommendations
     * @param array   $arrOptions  An optional options array
     *
     * @return integer The number of recommendations
     */
    public static function countPublishedByPids($arrPids, $blnFeatured=null, array $arrOptions=array())
    {
        if (empty($arrPids) || !\is_array($arrPids))
        {
            return 0;
        }

        $t = static::$strTable;
        $arrColumns = array("$t.pid IN(" . implode(',', array_map('\intval', $arrPids)) . ") AND $t.verified='1'");

        if ($blnFeatured === true)
        {
            $arrColumns[] = "$t.featured='1'";
        }
        elseif ($blnFeatured === false)
        {
            $arrColumns[] = "$t.featured=''";
        }

        if (!static::isPreviewMode($arrOptions))
        {
            $time = \Date::floorToMinute();
            $arrColumns[] = "($t.start='' OR $t.start<='$time') AND ($t.stop='' OR $t.stop>'" . ($time + 60) . "') AND $t.published='1'";
        }

        return static::countBy($arrColumns, null, $arrOptions);
    }
}
