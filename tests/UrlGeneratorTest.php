<?php

namespace tests\php;

use UrlGenerator\UrlGenerator;

class UrlGeneratorTest extends \PHPUnit_Framework_TestCase
{

    public function setUp()
    {
        $this->ug = new UrlGenerator(__DIR__ . '/test.json', []);
    }

    /**
     * @dataProvider getUrlDataProvider
     */
    public function testGetUrl($path, $params, $expected)
    {
        $this->assertEquals($expected, $this->ug->getUrl($path, $params));
    }

    public function getUrlDataProvider()
    {
        yield ['only_host', [], 'http://www.example.com'];

        yield ['very', [], 'http://www.example.com/very'];
        yield ['very.deep', [], 'http://www.example.com/very/deep'];
        yield ['very.deep.structure', [], 'http://www.example.com/very/deep/structure'];
        yield ['very.deep.structure.with', [], 'http://www.example.com/very/deep/structure/with'];
        yield ['very.deep.structure.with.advance', [], 'http://www.example.com/very/deep/structure/with/advance'];
        yield ['very.deep.structure.with.advance.heredity', [], 'http://www.example.com/very/deep/structure/with/advance#heredity'];

        yield ['path_test', ['some_param' => 'bla'], 'http://www.example.com?sp=bla'];
        yield ['path_test.with_leading_slash', ['some_param' => 'bla'], 'http://www.example.com/alohamora?sp=bla'];
        yield ['path_test.with_trailing_slash', ['some_param' => 'bla'], 'http://www.example.com/alohamora/?sp=bla'];
        yield ['path_test.with_trailing_slash', [], 'http://www.example.com/alohamora/'];
        yield ['path_test.with_both_slashes', ['some_param' => 'bla'], 'http://www.example.com/alohamora/?sp=bla'];
        yield ['path_test.with_both_slashes', [], 'http://www.example.com/alohamora/'];
        yield ['path_test.slash_only', ['some_param' => 'bla'], 'http://www.example.com/?sp=bla'];
        yield ['path_test.slash_only', [], 'http://www.example.com/'];

        yield ['query_params_test', [], 'http://www.example.com'];
        yield ['query_params_test', ['some_query_param' => 'v'], 'http://www.example.com?sqp=v'];
        yield ['query_params_test', ['some_query_param' => 'v', 'some_other_query_param' => 10], 'http://www.example.com?sqp=v&soqp=10'];
        yield ['query_params_test.without_params', [], 'http://www.example.com'];
        yield ['query_params_test.with_overloaded_params', ['another_param' => 'omnia'], 'http://www.example.com?ap=omnia'];

        yield ['fully_parametric_site', ['host' => 'yomama.com', 'port' => '666', 'path' => 'so/fat'], 'http://yomama.com:666/so/fat'];
        yield ['fully_parametric_site.with_param', ['host' => 'yomama.com', 'port' => '666', 'path' => 'so/fat', 'some_query_param' => 5], 'http://yomama.com:666/so/fat?q=5'];
        yield ['fully_parametric_site.with_param.and_fragment', ['host' => 'yomama.com', 'port' => '666', 'path' => 'so/fat', 'some_query_param' => 5, 'fragment' => 'hot'], 'http://yomama.com:666/so/fat?q=5#hot'];

        yield ['comparative_condition', ['env' => 'production', 'lang' => 'cz'], 'http://www.example.com/hledani'];
        yield ['comparative_condition', ['env' => 'production', 'lang' => 'cz', 'another' => 10], 'http://www.example.com/another'];
        yield ['comparative_condition', ['env' => 'production', 'lang' => 'cz', 'another' => 666], 'http://www.example.com/hledani'];
        yield ['comparative_condition', ['env' => 'production', 'lang' => 'pl'], 'http://www.example.com/sukanie'];
        yield ['comparative_condition', ['env' => 'production'], 'http://www.example.com'];

        yield ['comparative_condition', ['env' => 'dev', 'lang' => 'cz'], 'http://www.example.dev.czech/hledani'];
        yield ['comparative_condition', ['env' => 'dev', 'lang' => 'cz', 'another' => 10], 'http://www.example.dev.czech/another'];
        yield ['comparative_condition', ['env' => 'dev', 'lang' => 'cz', 'another' => "10"], 'http://www.example.dev.czech/another'];
        yield ['comparative_condition', ['env' => 'dev', 'lang' => 'pl'], 'http://www.example.dev.czech/sukanie'];
        yield ['comparative_condition', ['env' => 'dev'], 'http://www.example.dev.czech'];

        yield ['comparative_condition', ['env' => 'local', 'lang' => 'cz'], 'http://localhost/hledani'];
        yield ['comparative_condition', ['env' => 'local', 'lang' => 'cz', 'another' => 10], 'http://localhost/another'];
        yield ['comparative_condition', ['env' => 'local', 'lang' => 'cz', 'another' => "bad"], 'http://localhost/hledani'];
        yield ['comparative_condition', ['env' => 'local', 'lang' => 'pl'], 'http://localhost/sukanie'];
        yield ['comparative_condition', ['env' => 'local'], 'http://localhost'];

        yield ['comparative_condition', ['lang' => 'cz'], 'http://www.noenv.com/hledani'];
        yield ['comparative_condition', ['lang' => 'cz', 'another' => 10], 'http://www.noenv.com/another'];
        yield ['comparative_condition', ['lang' => 'cz', 'another' => "1"], 'http://www.noenv.com/hledani'];
        yield ['comparative_condition', ['lang' => 'pl'], 'http://www.noenv.com/sukanie'];
        yield ['comparative_condition', [], 'http://www.noenv.com'];

        yield ['comparative_condition.conflictive', [], 'http://www.noenv.com/outer'];
        yield ['comparative_condition.conflictive', ['lang'=> 'pl'], 'http://www.noenv.com/sukanie'];
        yield ['comparative_condition.conflictive', ['lang' => 'cz'], 'http://www.noenv.com/inner'];

        yield ['comparative_condition.conflictive', ['env' => 'production'], 'http://www.example.com/outer'];
        yield ['comparative_condition.conflictive', ['env' => 'production', 'lang' => 'pl'], 'http://www.example.com/sukanie'];
        yield ['comparative_condition.conflictive', ['env' => 'production', 'lang' => 'cz'], 'http://www.example.com/inner'];

        yield ['comparative_condition.conflictive', ['env' => 'dev'], 'http://www.example.dev.czech/outer'];
        yield ['comparative_condition.conflictive', ['env' => 'dev', 'lang' => 'pl'], 'http://www.example.dev.czech/sukanie'];
        yield ['comparative_condition.conflictive', ['env' => 'dev', 'lang' => 'cz'], 'http://www.example.dev.czech/inner'];

    }

    public function testConstructorParams()
    {
        $ug = new UrlGenerator(__DIR__ . '/test.json', ['host' => 'example.com', 'port' => '666']);

        $this->assertEquals('http://example.com:666/under-construction', $ug->getUrl('fully_parametric_site', ['path' => 'under-construction']));
        $this->assertEquals('http://cool.com:666/baby', $ug->getUrl('fully_parametric_site', ['host' => 'cool.com', 'path' => 'baby']));

        $this->expectException(\UrlGenerator\UrlGeneratorException::class);
        $ug->getUrl('fully_parametric_site', []);
    }

    public function testInvalidPath()
    {
        $this->expectException(\UrlGenerator\UrlGeneratorException::class);
        $this->ug->getUrl('some.non_existing.path');
    }

    public function testInvalidScheme()
    {
        $this->expectException(\UrlGenerator\UrlGeneratorException::class);
        $this->ug->getUrl('invalid_scheme');
    }

    public function testMissingConfiguration()
    {
        $this->expectException(\UrlGenerator\UrlGeneratorException::class);
        new UrlGenerator('non/existing/file', []);
    }

}
