<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Persisters;

use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Documents\BlogPost;
use Documents\Comment;

class DocumentPersisterFilterTest extends BaseTest
{
    public function testAddFilterToPreparedQuery()
    {
        $persister = $this->uow->getDocumentPersister('Documents\User');
        $filterCollection = $this->dm->getFilterCollection();

        $filterCollection->enable('testFilter');
        $testFilter = $filterCollection->getFilter('testFilter');
        $testFilter->setParameter('class', 'Documents\User');
        $testFilter->setParameter('field', 'username');
        $testFilter->setParameter('value', 'Tim');

        $preparedQuery = ['username' => 'Toby'];

        $expectedCriteria = [
        '$and' => [
            ['username' => 'Toby'],
            ['username' => 'Tim'],
        ],
        ];

        $this->assertSame($expectedCriteria, $persister->addFilterToPreparedQuery($preparedQuery));
    }

    public function testFilterCrieriaShouldAndWithMappingCriteriaOwningSide()
    {
        $blogPost = new BlogPost('Roger');
        $blogPost->addComment(new Comment('comment by normal user', new \DateTime(), false));
        $blogPost->addComment(new Comment('comment by admin', new \DateTime(), true));

        $this->dm->persist($blogPost);
        $this->dm->flush();
        $this->dm->clear();

        $filterCollection = $this->dm->getFilterCollection();

        $filterCollection->enable('testFilter');
        $testFilter = $filterCollection->getFilter('testFilter');
        $testFilter->setParameter('class', 'Documents\Comment');
        $testFilter->setParameter('field', 'isByAdmin');
        $testFilter->setParameter('value', false);

        $blogPost = $this->dm->getRepository('Documents\BlogPost')->find($blogPost->id);

        // Admin comments should be removed by the filter
        $this->assertCount(1, $blogPost->comments);

        /* Admin comments should be removed by the filter, and user comments
         * should be removed by the mapping criteria.
         */
        $this->assertCount(0, $blogPost->adminComments);
    }
}
