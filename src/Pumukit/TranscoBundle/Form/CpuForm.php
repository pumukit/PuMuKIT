<?php

namespace Pumukit\TranscoBundle\Form;

use Symfony\Component\Form\Form;
use Symfony\Component\Form\TextField;
use Symfony\Component\Form\TextareaField;
use Symfony\Component\Form\CheckboxField;

class CpuForm extends Form
{
  protected function configure()
  {
    //$this->add(new TextField('subject', array('max_length' => 100)));
    //$this->add(new TextareaField('message'));
    //$this->add(new TextField('sender'));
    //$this->add(new CheckboxField('ccmyself', array('required' => false)));

    $this->setDataClass('Pumukit\TranscoBundle\Entity\Cpu');
    $this->add('IP');
    $this->add('endpoint');
    $this->add('so_type');
    $this->add('num_jobs');
    $this->add('max_jobs');
    $this->add('login');
    $this->add('passwd');

      
  }

}