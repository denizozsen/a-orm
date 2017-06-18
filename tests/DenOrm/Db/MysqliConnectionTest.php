<?php

namespace AOrm\Db;

class MysqliConnectionTest extends \PHPUnit_Framework_TestCase
{
    /** @var MysqliConnection */
    private $mysqli_connection;

    public function setUp()
    {
        /** @var \mysqli $mock_mysqli */
        $mock_mysqli = $this->createMock('\mysqli');
        $this->mysqli_connection = new MysqliConnection($mock_mysqli);
    }

    /**
     * @test
     */
    public function first()
    {
        $sql = "SELECT * FROM my_table WHERE my_a = :my_a AND my_b = :something_else";
        $parameters = [ ':my_a' => 'value_for_a', ':something_else' => 'another_value' ];

        $expected_sql_result = "SELECT * FROM my_table WHERE my_a = ? AND my_b = ?";
        $expected_parameters_result = [ 'value_for_a', 'another_value' ];

        $this->mysqli_connection->convertNamedParameters($sql, $parameters);
        $this->assertEquals($expected_sql_result, $sql);
        $this->assertEquals($expected_parameters_result, $parameters);
    }
}
?>
