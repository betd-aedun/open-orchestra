<?php
/**
 * This file is part of the PHPOrchestra\CMSBundle.
 *
 * @author Nicolas ANNE <nicolas.anne@businessdecision.com>
 */

namespace PHPOrchestra\CMSBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use PHPOrchestra\CMSBundle\Form\DataTransformer\JsonToAreasTransformer;

class NodeChoiceType extends AbstractType
{

    public $choices = null;

    /**
     * Constructor, require documentLoader service
     * 
     * @param $documentLoader
     */
    public function __construct($documentLoader)
    {
        $nodes = $documentLoader->getDocuments('Node', array());
        $this->choices[''] = '--------';
        foreach ($nodes as $node) {
            $this->choices[$node->getNodeId()] = $node->getName();
        }
    }
    
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'choices' => $this->choices,
            )
        );
    }
    
    public function getParent()
    {
        return 'choice';
    }

    public function getName()
    {
        return 'orchestra_node_choice';
    }
}