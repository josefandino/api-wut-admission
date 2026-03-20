<?php

namespace App\Application\Actions\Fraud;

use App\Application\Actions\Action;
use App\Shared\IpIntelligence;

class FraudAnalyticsAction extends Action
{
    protected function action(): \Psr\Http\Message\ResponseInterface
    {
        error_log("Fraud analytics access - IP: " . $this->getClientIp());

        try {
            $suspiciousLogs = IpIntelligence::getSuspiciousLogs(50);
            $statistics = IpIntelligence::getFraudStatistics();
            $incidentsByCountry = IpIntelligence::getIncidentsByCountry();

            return $this->respondWithData([
                'suspicious_logs' => $suspiciousLogs,
                'statistics' => $statistics,
                'incidents_by_country' => $incidentsByCountry
            ]);
        } catch (\Exception $e) {
            return $this->respondWithError('Error al obtener análisis de fraude: ' . $e->getMessage(), 500);
        }
    }
}
