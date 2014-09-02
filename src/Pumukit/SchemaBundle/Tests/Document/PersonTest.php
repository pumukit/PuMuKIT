<?php
namespace Pumukit\SchemaBundle\Tests\Document;

use Pumukit\SchemaBundle\Document\Person;

class PersonTest extends \PHPUnit_Framework_TestCase
{
	public function testGetterAndSetter()
	{
		$email = 'email@email.com';
		$name = 'name';
		$web = 'web';
		$phone = 'phone';
		$honorific = 'honorific';
		$firm = 'firm';
		$post = 'post';
		$bio = 'Eu son Balbino. Un rapaz de aldea. Coma quen di, un ninguén. E ademais, pobre';

		$person = new Person();

		$person->setEmail($email);
		$person->setName($name);
		$person->setWeb($web);
		$person->setPhone($phone);
		$person->setHonorific($honorific);
		$person->setFirm($firm);
		$person->setPost($post);
		$person->setBio($bio);

		$this->assertEquals($email, $person->getEmail());
		$this->assertEquals($name, $person->getName());
		$this->assertEquals($web, $person->getWeb());
		$this->assertEquals($phone, $person->getPhone());
		$this->assertEquals($honorific, $person->getHonorific());
		$this->assertEquals($firm, $person->getFirm());
		$this->assertEquals($post, $person->getPost());
		$this->assertEquals($bio, $person->getBio());
	}
}
