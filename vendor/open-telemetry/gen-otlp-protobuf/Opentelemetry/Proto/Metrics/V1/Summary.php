<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: opentelemetry/proto/metrics/v1/metrics.proto

namespace Opentelemetry\Proto\Metrics\V1;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Summary metric data are used to convey quantile summaries,
 * a Prometheus (see: https://prometheus.io/docs/concepts/metric_types/#summary)
 * and OpenMetrics (see: https://github.com/OpenObservability/OpenMetrics/blob/4dbf6075567ab43296eed941037c12951faafb92/protos/prometheus.proto#L45)
 * data type. These data points cannot always be merged in a meaningful way.
 * While they can be useful in some applications, histogram data points are
 * recommended for new applications.
 *
 * Generated from protobuf message <code>opentelemetry.proto.metrics.v1.Summary</code>
 */
class Summary extends \Google\Protobuf\Internal\Message
{
    /**
     * Generated from protobuf field <code>repeated .opentelemetry.proto.metrics.v1.SummaryDataPoint data_points = 1;</code>
     */
    private $data_points;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type \Opentelemetry\Proto\Metrics\V1\SummaryDataPoint[]|\Google\Protobuf\Internal\RepeatedField $data_points
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Opentelemetry\Proto\Metrics\V1\Metrics::initOnce();
        parent::__construct($data);
    }

    /**
     * Generated from protobuf field <code>repeated .opentelemetry.proto.metrics.v1.SummaryDataPoint data_points = 1;</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getDataPoints()
    {
        return $this->data_points;
    }

    /**
     * Generated from protobuf field <code>repeated .opentelemetry.proto.metrics.v1.SummaryDataPoint data_points = 1;</code>
     * @param \Opentelemetry\Proto\Metrics\V1\SummaryDataPoint[]|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setDataPoints($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::MESSAGE, \Opentelemetry\Proto\Metrics\V1\SummaryDataPoint::class);
        $this->data_points = $arr;

        return $this;
    }

}

