<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Google\Cloud\BigQuery\BigQueryClient;
use Google\Cloud\Core\ExponentialBackoff;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Dotenv\Dotenv;

class StackOverflowSearch extends AbstractController
{
    /**
     * @Route("/stack_overflow_search", name="stack_overflow_search_list")
     *
     */

    public function list()
    {
         $query = 'SELECT id, title FROM `bigquery-public-data.stackoverflow.posts_questions` LIMIT 50';

         $config = [
            'keyFilePath' => $_ENV['KEY_FILE_PATH'],
            'projectId' => $_ENV['PROJECT_ID'],
         ];

        $bigQuery = new BigQueryClient( $config );
        $jobConfig = $bigQuery->query($query);
        $job = $bigQuery->startQuery($jobConfig);

        $backoff = new ExponentialBackoff(10);
        $backoff->execute(function () use ($job) {
            $job->reload();
            if (!$job->isComplete()) {
                throw new Exception('Job has not yet completed', 500);
            }
        });
        
        $queryResults = $job->queryResults();

        $results = [];
        
        foreach ($queryResults as $row) {
            array_push($results , $row);
        }

        $response = new JsonResponse();
        $response->setData([  
            'success' => true,
            'data' =>  $results
        ]);

        return $response;
    }
}







