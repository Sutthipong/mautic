<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ApiBundle\Entity\oAuth1;

use Mautic\CoreBundle\Entity\CommonRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Mautic\UserBundle\Entity\User;

/**
 * ConsumerRepository
 */
class ConsumerRepository extends CommonRepository
{

    public function getUserClients(User $user)
    {
        $q = $this->_em->createQueryBuilder();

        $q->select('c')
            ->from('MauticApiBundle:oAuth1\Consumer', 'c')
            ->leftJoin('c.accessTokens', 'a')
            ->where($q->expr()->eq('a.user', ':user'))
            ->setParameter('user', $user)
            ->groupBy('c.id');

        return $q->getQuery()->getResult();
    }

    /**
     * @param Consumer $consumer
     * @param User     $user
     */
    public function deleteAccessTokens(Consumer $consumer, User $user)
    {
        $qb = $this->_em->createQueryBuilder();

        $qb->delete('MauticApiBundle:oAuth1\AccessToken', 'a')
            ->andWhere(
                $qb->expr()->andX(
                    $qb->expr()->eq('a.consumer', ':consumer'),
                    $qb->expr()->eq('a.user', ':user')
                )
            )
            ->setParameters(array(
                'consumer' => $consumer,
                'user'     => $user
            ));

        $qb->getQuery()->execute();
    }

    /**
     * {@inheritdoc}
     *
     * @param array $args
     * @return Paginator
     */
    public function getEntities($args = array())
    {
        $q = $this
            ->createQueryBuilder('c');

        if (!$this->buildClauses($q, $args)) {
            return array();
        }

        $query = $q->getQuery();
        $result = new Paginator($query);
        return $result;
    }

    protected function addCatchAllWhereClause(&$q, $filter)
    {
        $unique  = $this->generateRandomParameterName(); //ensure that the string has a unique parameter identifier
        $string  = ($filter->strict) ? $filter->string : "%{$filter->string}%";

        $expr = $q->expr()->orX(
            $q->expr()->like('c.name',  ':'.$unique),
            $q->expr()->like('c.callback', ':'.$unique)
        );

        if ($filter->not) {
            $expr = $q->expr()->not($expr);
        }

        return array(
            $expr,
            array("$unique" => $string)
        );
    }

    protected function getDefaultOrder()
    {
        return array(
            array('c.name', 'ASC')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getTableAlias()
    {
        return 'c';
    }
}
