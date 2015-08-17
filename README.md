# Teon PHP DMARC Report Parser

PHP library for parsing DMARC aggregate report files into ordinary PHP arrays.
If it is supplied a compressed report, it uncompresses it automatically.



  * [Teon PHP DMARC Report Parser](#teon-php-dmarc-report-parser)
    * [Basic usage](basic-usage)
    * [Sample output](#sample-output)
    * [License](#license)



## Basic usage

``php
// Parse file like this...
$myReportData = \Teon\Dmarc\Parser\AggregateReportParser::parseFile($myReportFilePath);

// ...or parse XML content directly, like this:
$myReportData = \Teon\Dmarc\Parser\AggregateReportParser::parseXmlContent($myReportXmlContent);
```



## Sample output

Output of ```print_r($myReportData)```

```
stdClass Object
(
    [version] => 1.0
    [report_metadata] => stdClass Object
        (
            [org_name] => teon.si
            [email] => MAILER-DAEMON@teon.si
            [extra_contact_info] => stdClass Object
                (
                )

            [report_id] => sth@teon.si
            [date_range] => stdClass Object
                (
                    [begin] => 1439337600
                    [end] => 1439423999
                )

        )

    [policy_published] => stdClass Object
        (
            [domain] => a2o.si
            [adkim] => r
            [aspf] => r
            [p] => none
            [sp] => none
            [pct] => 100
        )

    [records] => Array
        (
            [0] => stdClass Object
                (
                    [row] => stdClass Object
                        (
                            [source_ip] => 1.1.1.1
                            [count] => 1
                            [policy_evaluated] => stdClass Object
                                (
                                    [disposition] => none
                                    [dkim] => fail
                                    [spf] => fail
                                )

                        )

                    [identifiers] => stdClass Object
                        (
                            [header_from] => a2o.si
                            [envelope_from] => forward.otherdomain.tld
                        )

                    [auth_results] => stdClass Object
                        (
                            [dkim] => stdClass Object
                                (
                                    [domain] => a2o.si
                                    [selector] => 20150717
                                    [result] => permerror
                                )

                            [spf] => stdClass Object
                                (
                                    [domain] => forward.otherdomain.tld
                                    [scope] => mfrom
                                    [result] => pass
                                )

                        )

                )

            [1] => stdClass Object
                (
                    [row] => stdClass Object
                        (
                            [source_ip] => 2.2.2.2
                            [count] => 1
                            [policy_evaluated] => stdClass Object
                                (
                                    [disposition] => none
                                    [dkim] => fail
                                    [spf] => fail
                                )

                        )

                    [identifiers] => stdClass Object
                        (
                            [header_from] => a2o.si
                            [envelope_from] => forward.otherdomain.tld
                        )

                    [auth_results] => stdClass Object
                        (
                            [dkim] => stdClass Object
                                (
                                    [domain] => a2o.si
                                    [selector] => 20150717
                                    [result] => permerror
                                )

                            [spf] => stdClass Object
                                (
                                    [domain] => forward.otherdomain.tld
                                    [scope] => mfrom
                                    [result] => pass
                                )

                        )

                )

        )

)
```



## License

The MIT License (MIT)

Copyright (c) 2015 Teon d.o.o.

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
