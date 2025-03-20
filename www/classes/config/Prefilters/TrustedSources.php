<?php

require_once('helpers/PrefHandler.php');
require_once('config/PreFilter.php');

class TrustedSources extends PreFilter
{

    /**
     * prefilter properties
     * @var array
     */
    private $specpref_ = [
        'use_alltrusted' => 1,
        'use_authservers' => 1,
        'useSPFOnLocal' => 1,
        'useSPFOnGlobal' => 1,
        'domainsToSPF' => '',
        'authservers' => '',
        'authstring' => ''
    ];

    private $form_;

    public function addSpecPrefs()
    {
        $this->addPrefSet('trustedSources', 'ts', $this->specpref_);
    }

    public function getSpecificTMPL()
    {
        return "prefilters/TrustedSources.tmpl";
    }

    public function getSpeciticReplace($template, $form)
    {
        global $lang_;
        $this->form_ = $form;

        $ret = [
            '__FORM_USEALLTRUSTED__' => $form->checkbox('use_alltrusted', 1, $this->getPref('use_alltrusted'), '', 1),
            '__FORM_USESPFONLOCAL__' => $form->checkbox('useSPFOnLocal', 1, $this->getPref('useSPFOnLocal'), '', 1),
            '__FORM_USESPFONGLOBAL__' => $form->checkbox('useSPFOnGlobal', 1, $this->getPref('useSPFOnGlobal'), '', 1),
            '__FORM_AUTHSERVERS__' => $form->input('authservers', 35, $this->getPref('authservers')),
            '__FORM_AUTHSTRING__' => $form->input('authstring', 35, $this->getPref('authstring')),
            '__FORM_DOMAINSTOSPF__' => $form->textarea('domainsToSPF', 35, 5, $this->getPref('domainsToSPF')),
        ];

        return $ret;
    }

    public function subload()
    {
    }
    public function subsave($posted)
    {
        if ($this->getPref('authservers') == "") {
            $this->setPref('use_authservers', 0);
        } else {
            $this->setPref('use_authservers', 1);
        }
    }
}
