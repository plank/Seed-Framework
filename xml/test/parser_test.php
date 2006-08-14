<?php


class ParserTester extends UnitTestCase {
	/**
	 * @var XmlParser
	 */
	var $XmlParser;
	
	function setup() {
		$this->parser = new XmlParser();
		
	}
	
	function test_single_node() {
		
		$data = "<singlenode>data</singlenode>";
		
		$document = $this->parser->parse($data);
		
		$this->assertIsA($document, 'XmlNode');
		$this->assertEqual('singlenode', $document->get_name());
		$this->assertEqual('data', $document->get_data());
		
	}
	
	function test_nested_nodes() {
		$data = "<node1><node2>data</node2></node1>";
		
		$document = $this->parser->parse($data);
		
		// make sure the root node got created properly
		$this->assertEqual('node1', $document->get_name());

		// make sure the first child got created properly too
		$child = $document->get_child(0);
		$this->assertIsA($child, 'XmlNode');
		$this->assertEqual('node2', $child->get_name());
		
		// make sure it's the only child
		$this->assertEqual(array($child), $document->get_children());
		
		// and that it contains the string data
		$this->assertEqual('data', $child->get_data());
		
		// test direct access
		$this->assertEqual($document->node2[0]->get_data(), 'data');
		
	}
	
	function test_deep_nesting() {
		$data = "<node><node><node>data</node></node></node>";
		
		$document = $this->parser->parse($data);
		
		$this->assertEqual($document->node[0]->node[0]->get_data(), 'data');
		
		
	}
	
	function test_siblings() {
		$data = "<root><node1>one</node1><node1>two</node1><node2>three</node2><root>";
		
		$document = $this->parser->parse($data);
		
		$this->assertEqual($document->node1[0]->get_data(), 'one');
		$this->assertEqual($document->node1[1]->get_data(), 'two');
		$this->assertEqual($document->node2[0]->get_data(), 'three');
		
	}
	
	/**
	 * Test a self closing node that contains attribute
	 */
	function test_attributes_and_self_closing() {
		$data = "<root><node type='foo' value='bar' /></root>";	
		
		$document = $this->parser->parse($data);
		
		$this->assertEqual($document->node[0]->get_attributes(), array('type'=>'foo', 'value'=>'bar'));
		$this->assertEqual($document->node[0]->get_attribute('type'), 'foo');
		$this->assertEqual($document->node[0]->get_attribute('typse'), null);
		
		// test attribute shortcuts
		$this->assertEqual($document->node[0]->type, 'foo');
		$this->assertNull($document->node[0]->typse);

		
		$this->assertNull($document->node[0]->get_data());
		
	}
	
	/**
	 * Make sure cdata sections work properly
	 */
	function test_cdata() {
		$data = "<foo><![CDATA[<bar>]]></foo>";
		
		$document = $this->parser->parse($data);
		
		$this->assertEqual('<bar>', $document->get_data());
		
	}
	
	/**
	 * Test a variety of common errors
	 */
	function test_bad_xml() {
		// not well formed
		$data = "<root><node </root>";
		
		$document = $this->parser->parse($data);
		$this->assertError('Error 4 at 1:12: Not well-formed');
		
		// mismatched tags
		$data = "<foo><bar></foo></bar>";
		
		$document = $this->parser->parse($data);
		$this->assertError('Error 7 at 1:12: Mismatched tag');
		
		$data = "<foo><xml /></foo>";
		$document = $this->parser->parse($data);
		$this->assertError('Error 4 at 1:5: Not well-formed');

		$data = "<0foo><1bar /></0foo>";
		$document = $this->parser->parse($data);
		$this->assertError('Error 4 at 1:1: Not well-formed');
		
		
	}

	/**
	 * Test to see if we can properly parse a simple atom feed
	 */
	function test_atom_example() {
$data = <<<EOF
<?xml version="1.0" encoding="utf-8"?>
<feed xmlns="http://www.w3.org/2005/Atom">

  <title>Example Feed</title>
  <link href="http://example.org/"/>
  <updated>2003-12-13T18:30:02Z</updated>
  <author>
    <name>John Doe</name>
  </author>
  <id>urn:uuid:60a76c80-d399-11d9-b93C-0003939e0af6</id>

  <entry>
    <title>Atom-Powered Robots Run Amok</title>
    <link href="http://example.org/2003/12/13/atom03"/>
    <id>urn:uuid:1225c695-cfb8-4ebb-aaaa-80da344efa6a</id>
    <updated>2003-12-13T18:30:02Z</updated>
    <summary>Some text.</summary>
  </entry>

</feed>
EOF;

		$document = $this->parser->parse($data);

		$this->assertEqual($document->get_attribute('xmlns'), "http://www.w3.org/2005/Atom"); 
		$this->assertEqual($document->title[0]->get_data(), 'Example Feed');
		$this->assertEqual($document->link[0]->href, "http://example.org/");
		$this->assertEqual($document->updated[0]->get_data(), '2003-12-13T18:30:02Z');
		$this->assertEqual($document->author[0]->name[0]->get_data(), 'John Doe');
		$this->assertEqual($document->id[0]->get_data(), 'urn:uuid:60a76c80-d399-11d9-b93C-0003939e0af6');
		
		$this->assertEqual($document->entry[0]->title[0]->get_data(), 'Atom-Powered Robots Run Amok');
		$this->assertEqual($document->entry[0]->link[0]->href, "http://example.org/2003/12/13/atom03");
	    $this->assertEqual($document->entry[0]->id[0]->get_data(), 'urn:uuid:1225c695-cfb8-4ebb-aaaa-80da344efa6a');
	    $this->assertEqual($document->entry[0]->updated[0]->get_data(), '2003-12-13T18:30:02Z');
	    $this->assertEqual($document->entry[0]->summary[0]->get_data(), 'Some text.');
		
	}
	
}


?>