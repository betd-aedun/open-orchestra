<?php
/**
 * This file is part of the PHPOrchestra\CMSBundle.
 *
 * @author Noël Gilain <noel.gilain@businessdecision.com>
 */

namespace PHPOrchestra\CMSBundle\Controller\BackOfficeView;

use PHPOrchestra\CMSBundle\Controller\TableViewController;
use Model\PHPOrchestraCMSBundle\ContentType;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @Route("/contenttype")
 */
class ContentTypeController extends TableViewController
{
    /**
     * (non-PHPdoc)
     * @see src/symfony2/vendor/php-orchestra/cms-bundle/PHPOrchestra/CMSBundle/Controller/PHPOrchestra
     * \CMSBundle\Controller.TableViewController::init()
     */
    public function init()
    {
        $this->setEntity('ContentType');
        $this->setMainTitle('Type de contenus');
      //  $this->setCriteria(array('deleted' => false));
        $this->callback['selectLanguageName'] = function($jsonLanguages)
        {
            $languages = (array) json_decode($jsonLanguages);
            $value = '';
            if (is_array($languages) && isset($languages['fr'])) {
                $value = $languages['fr'];
            }
            return $value;
        };
    }

    /**
     * (non-PHPdoc)
     * @see src/symfony2/vendor/php-orchestra/cms-bundle/PHPOrchestra/CMSBundle/Controller/PHPOrchestra
     * \CMSBundle\Controller.TableViewController::setColumns()
     */
    public function setColumns()
    {
        $this->columns = array(
            array('name' => 'contentTypeId', 'search' => 'text', 'label' => 'Identifiant'),
            array('name' => 'name', 'search' => 'text', 'label' => 'Nom', 'callback' => 'selectLanguageName'),
            array('name' => 'version', 'search' => 'text', 'label' => 'Version'),
            array('name' => 'status', 'search' => 'text', 'label' => 'Statut'),
            array('button' =>'modify'),
            array('button' =>'delete')
        );
    }

    /**
     * (non-PHPdoc)
     * @see src/symfony2/vendor/php-orchestra/cms-bundle/PHPOrchestra/CMSBundle/Controller/PHPOrchestra
     * \CMSBundle\Controller.TableViewController::editEntity()
     */
    public function editEntity(Request $request, $documentId)
    {
        $documentManager = $this->container->get('phporchestra_cms.documentmanager');
        
        if (empty($documentId)) {
            $contentType = $documentManager->createDocument('ContentType');
            $contentType->save();
        } else {
            $contentType = $documentManager->getDocumentById('ContentType', $documentId);
        }
        
        if ($contentType->getStatus() != ContentType::STATUS_DRAFT) {
            $contentType->generateDraft();
        }
        
        $documentId = (string) $contentType->getId();
        
        $form = $this->createForm('contentType', $contentType);
        
        if ($request->getMethod() == 'POST') {
            $form->handleRequest($request);
            
            if ($contentType->new_field != '') {
                $contentType->save();
                $form = $this->createForm('contentType', $contentType);
            }
            
            if ($form->isValid() && $contentType->new_field == '') {
                $this->deleteOtherStatusVersions(
                    $contentType->getContentTypeId(),
                    $contentType->getStatus(),
                    $documentId
                );
                $contentType->save();
                $success = true;
                $data = $this->generateUrlValue('catalog');
            } else {
                $success = false;
                $render = $this->getRender($form, $documentId);
                $data = $render->getContent();
            }
            
            return new JsonResponse(
                array(
                    'success' => $success,
                    'data' => $data
                )
            );
        }
        
        return $this->getRender($form, $documentId);
    }

    /**
     * Get the form render
     * 
     * @param unknown_type $form
     * @param string $documentId
     */
    protected function getRender($form, $documentId)
    {
        $select = $this->render(
            'PHPOrchestraCMSBundle:BackOffice/Content:customFieldSelect.html.twig',
            array(
                'availableFields' => $this->container->getParameter('php_orchestra.custom_types'),
                'saveAction' => $this->generateUrlValue('edit', $documentId)
            )
        );
        
        return $this->render(
            'PHPOrchestraCMSBundle:BackOffice/Content:contentTypeForm.html.twig',
            array(
                'form' => $form->createView(),
                'ribbon' => $this->saveButton($documentId) . $this->backButton() . $select->getContent(),
                'mainTitle' => $this->getMainTitle(),
                'tableTitle' => $this->getTableTitle(),
            )
        );
    }

    /**
     * Keep only one version of the status $status for the document $documentId
     * 
     * @param string $contentTypeId
     * @param string $status
     * @param string $documentId
     */
    protected function deleteOtherStatusVersions($contentTypeId, $status, $documentId)
    {
        $documentManager = $this->container->get('phporchestra_cms.documentmanager');
        
        $versions = $documentManager->getDocuments(
            'ContentType',
            array(
                'contentTypeId' => $contentTypeId,
                'status' => $status
            )
        );
        
        foreach ($versions as $version) {
            if ($version->getId() != $documentId) {
                $version->delete();
            }
        }
        
        return true;
    }

    /**
     * (non-PHPdoc)
     * @see src/symfony2/vendor/php-orchestra/cms-bundle/PHPOrchestra/CMSBundle/Controller/PHPOrchestra
     * \CMSBundle\Controller.TableViewController::deleteEntity()
     */
    public function deleteEntity(Request $request, $documentId)
    {
        $documentManager = $this->get('phporchestra_cms.documentmanager');
        
        $contentType = $documentManager->getDocumentById('ContentType', $documentId);
        $contentTypeId = $contentType->getContentTypeId();
        $contentTypeVersions = $documentManager->getDocuments('ContentType', array('contentTypeId' => $contentTypeId));
        
        foreach ($contentTypeVersions as $contentTypeVersion) {
            $contentTypeVersion->markAsDeleted();
        }
        
        return $this->redirect(
            $this->generateUrlValue('catalog')
        );
    }
    
    /**
     * Return a list of contentTypes alloawed for $siteId
     * 
     * @param string $siteId
     */
    public function ajaxMenuAction($language, $siteId)
    {
        $documentManager = $this->container->get('phporchestra_cms.documentmanager');
        $contentTypes = $documentManager->getContentTypesInLastVersion();
        
        $contentTypesArray = array();
        
        foreach($contentTypes as $contentType) {
            $languages = (array) json_decode($contentType['name']);
            $name = 'Unknown name in ' . $language;
            if (isset($languages[$language])) {
                $name = $languages[$language];
            }
            
            $contentTypesArray[] = array(
                'url' => $this->container->get('router')->generate(
                    'phporchestra_cms_backofficeview_content_index',
                    array(
                        'action' => 'catalog',
                        'contentTypeId' => $contentType['_id']
                    )
                ),
                'label' => htmlentities($name)
            );
        }
        
        return new JsonResponse($contentTypesArray);
    }
}
