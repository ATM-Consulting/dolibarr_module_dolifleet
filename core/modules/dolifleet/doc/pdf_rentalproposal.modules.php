<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2012 Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin		<regis.houssin@capnetworks.com>
 * Copyright (C) 2014-2015 Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2018      Frédéric France      <frederic.france@netlogic.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 *	\file       htdocs/core/modules/dolifleet/doc/pdf_rentalproposal.modules.php
 *	\ingroup    dolifleet
 *	\brief      Fichier de la classe permettant de generer les bordereaux envoi au modele saumon
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commondocgenerator.class.php';
require_once dol_buildpath('dolifleet/core/modules/dolifleet/modules_rentalproposal.php');
require_once dol_buildpath('dolifleet/class/dictionaryVehiculeActivityType.class.php');
require_once dol_buildpath('dolifleet/class/dictionaryVehiculeType.class.php');
require_once __DIR__.'/../../../../class/rentalProposal.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';


/**
 *	Classe permettant de generer les borderaux envoi au modele saumon
 */
class pdf_rentalproposal extends ModelePDFRentalproposal
{
	var $emetteur;	// Objet societe qui emet

	public $dictTypeAct;
	public $dictTypeVeh;

	public $heightForFooter;

	public $withForImmat = 30;
	public $widthForDateExploit = 40;
	public $widthForDesc = 80;
	public $widthForTotalHT = 40;

	/** @var TCPDF $pdf */
	public $pdf;

	public $h_ligne = 6;

	/**
	 *	Constructor
	 *
	 *	@param	DoliDB	$db		Database handler
	 */
	function __construct($db=0)
	{
		global $conf,$langs,$mysoc;

		$this->db = $db;
		$this->name = "rentalproposal";
		$this->description = $langs->trans("DocumentModelStandardPDF");

		$this->type = 'pdf';
		$formatarray=pdf_getFormat();
		$this->page_largeur = $formatarray['width'];
		$this->page_hauteur = $formatarray['height'];
		$this->format = array($this->page_largeur,$this->page_hauteur);
		$this->marge_gauche=isset($conf->global->MAIN_PDF_MARGIN_LEFT)?$conf->global->MAIN_PDF_MARGIN_LEFT:10;
		$this->marge_droite=isset($conf->global->MAIN_PDF_MARGIN_RIGHT)?$conf->global->MAIN_PDF_MARGIN_RIGHT:10;
		$this->marge_haute =isset($conf->global->MAIN_PDF_MARGIN_TOP)?$conf->global->MAIN_PDF_MARGIN_TOP:10;
		$this->marge_basse =isset($conf->global->MAIN_PDF_MARGIN_BOTTOM)?$conf->global->MAIN_PDF_MARGIN_BOTTOM:10;

		$this->option_logo = 1;

		// Get source company
		$this->emetteur=$mysoc;
		if (! $this->emetteur->country_code) $this->emetteur->country_code=substr($langs->defaultlang,-2);    // By default if not defined


		$this->tabTitleHeight = 5; // default height

		$this->dictTypeAct = new dictionaryVehiculeActivityType($db);
		$this->dictTypeVeh = new dictionaryVehiculeType($db);

	}

	/**
	 *	Function to build pdf onto disk
	 *
	 *	@param		Object		$object			Object rentalproposal to generate (or id if old method)
	 *	@param		Translate	$outputlangs		Lang output object
	 *  @param		string		$srctemplatepath	Full path of source filename for generator using a template file
	 *  @param		int			$hidedetails		Do not show line details
	 *  @param		int			$hidedesc			Do not show desc
	 *  @param		int			$hideref			Do not show ref
	 *  @return     int         	    			1=OK, 0=KO
	 */
	function write_file($object,$outputlangs,$srctemplatepath='',$hidedetails=0,$hidedesc=0,$hideref=0)
	{
		global $user,$conf,$langs,$hookmanager;

		$this->object = $object;

		$upload_dir = $conf->dolifleet->multidir_output[$conf->entity];

		$this->outputlangs = $outputlangs;

		if (! is_object($this->outputlangs)) $this->outputlangs=$langs;
		// For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
		if (! empty($conf->global->MAIN_USE_FPDF)) $this->outputlangs->charset_output='ISO-8859-1';

		// Translations
		$this->outputlangs->loadLangs(array("main", "bills", "products", "dict", "companies", "propal", "deliveries", "sendings", "productbatch"));

		if ($upload_dir)
		{
			// Definition de $dir et $file
			if ($object->specimen)
			{
				$dir = $upload_dir."/sending";
				$file = $dir . "/SPECIMEN.pdf";
			}
			else
			{
				$expref = dol_sanitizeFileName($object->ref);
				$dir = $upload_dir."/" . $expref;
				$file = $dir . "/" . $expref . ".pdf";
			}

			if (! file_exists($dir))
			{
				if (dol_mkdir($dir) < 0)
				{
					$this->error=$langs->transnoentities("ErrorCanNotCreateDir",$dir);
					return 0;
				}
			}

			if (file_exists($dir))
			{
				// Add pdfgeneration hook
				if (! is_object($hookmanager))
				{
					include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
					$hookmanager=new HookManager($this->db);
				}
				$hookmanager->initHooks(array('pdfgeneration'));
				$parameters=array('file'=>$file,'object'=>$object,'outputlangs'=>$this->outputlangs);
				global $action;
				$reshook=$hookmanager->executeHooks('beforePDFCreation',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks

				// Set nblignes with the new object lines content after hook
				$nblignes = 0 ;
				if(!empty($object->lines)){
					$nblignes = count($object->lines);
				}

				$this->pdf=pdf_getInstance($this->format);
				$this->default_font_size = pdf_getPDFFontSize($this->outputlangs);
				$heightforinfotot = 8;	// Height reserved to output the info and total part
				$heightforfreetext= (isset($conf->global->MAIN_PDF_FREETEXT_HEIGHT)?$conf->global->MAIN_PDF_FREETEXT_HEIGHT:5);	// Height reserved to output the free text on last page
				$heightforfooter = $this->marge_basse + 20;	// Height reserved to output the footer (value include bottom margin)
				if ($conf->global->MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS >0) $heightforfooter+= 6;
				$this->pdf->SetAutoPageBreak(1,0);

				if (class_exists('TCPDF'))
				{
					$this->pdf->setPrintHeader(false);
					$this->pdf->setPrintFooter(false);
				}
				$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs));
				// Set path to the background PDF File
				if (! empty($conf->global->MAIN_ADD_PDF_BACKGROUND))
				{
					$pagecount = $this->pdf->setSourceFile($conf->mycompany->dir_output.'/'.$conf->global->MAIN_ADD_PDF_BACKGROUND);
					$tplidx = $this->pdf->importPage(1);
				}

				$this->pdf->Open();
				$pagenb=0;
				$this->pdf->SetDrawColor(128,128,128);

				if (method_exists($this->pdf,'AliasNbPages')) $this->pdf->AliasNbPages();

				$this->pdf->SetTitle($this->outputlangs->convToOutputCharset($object->ref));
				$this->pdf->SetSubject($this->outputlangs->transnoentities("Processrules"));
				$this->pdf->SetCreator("Dolibarr ".DOL_VERSION);
				$this->pdf->SetAuthor($this->outputlangs->convToOutputCharset($user->getFullName($this->outputlangs)));
				$this->pdf->SetKeyWords($this->outputlangs->convToOutputCharset($object->ref)." ".$this->outputlangs->transnoentities("Processrules"));
				if (! empty($conf->global->MAIN_DISABLE_PDF_COMPRESSION)) $this->pdf->SetCompression(false);

				$this->pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right

				// New page
				$this->pdf->AddPage();
				$curentY = $this->prepareNewPage($this->pdf, true);

				$this->total_ht = 0;
				$this->subtotals = array();

				// Loop on each  procedure
				if(!empty($object->lines)){

					foreach ($object->lines as $l) {
						$this->total_ht+= $l->total_ht;
						$this->subtotals[$l->activity_type]['total'] += $l->total_ht;
						$this->subtotals[$l->activity_type][$l->fk_vehicule_type] += $l->total_ht;
					}

					$this->printProposalLines($object->lines);

				}


				// Pied de page
				if (method_exists($this->pdf,'AliasNbPages')) $this->pdf->AliasNbPages();

				$this->pdf->Close();

				$this->pdf->Output($file,'F');

				// Add pdfgeneration hook
				$hookmanager->initHooks(array('pdfgeneration'));
				$parameters=array('file'=>$file,'object'=>$object,'outputlangs'=>$this->outputlangs);
				global $action;
				$reshook=$hookmanager->executeHooks('afterPDFCreation',$parameters,$this,$action);    // Note that $action and $object may have been modified by some hooks

				if (! empty($conf->global->MAIN_UMASK))
					@chmod($file, octdec($conf->global->MAIN_UMASK));

				$this->result = array('fullpath'=>$file);

				return 1;	// No error
			}
			else
			{
				$this->error=$langs->transnoentities("ErrorCanNotCreateDir",$dir);
				return 0;
			}
		}
		else
		{
			$this->error=$langs->transnoentities("ErrorConstantNotDefined","EXP_OUTPUTDIR");
			return 0;
		}
	}

	function printProposalLines($lines = array())
	{
		$typeAct = $typeVeh = $index = 0;
		$this->nbLines = count($lines);

		foreach ($lines as $line)
		{

			if ($typeAct !== $line->activity_type)
			{
				$this->printActivity($line->activity_type);

				$typeAct = $line->activity_type;
				$typeVeh = 0;
			}

			if ($typeVeh !== $line->fk_vehicule_type)
			{
				$this->printVehiculeType($typeAct, $line->fk_vehicule_type);

				$typeVeh = $line->fk_vehicule_type;
			}
			$isLast = $index == $this->nbLines -1;

			$posYbefore = $this->pdf->GetY();
			$this->pdf->startTransaction();
			$this->printProposalLine($line, $isLast);

			$posYafter = $this->pdf->GetY();

			if ($posYafter > $this->page_hauteur - $this->heightForFooter - $this->marge_basse)
			{
				$this->pdf = $this->pdf->rollbackTransaction();
				$this->pdf->Line($this->marge_gauche, $posYbefore, $this->page_largeur - $this->marge_gauche, $posYbefore);

				$this->pdf->AddPage();
				$this->prepareNewPage($this->pdf, true);
				$this->printProposalLine($line, $isLast);
			}
			else
			{
				$this->pdf->commitTransaction();
			}

			$index++;
		}
	}

	function printProposalLine($line, $isLast = false)
	{
		$borderStyle = 'LR';
		if ($isLast) $borderStyle = 'LBR';

		$posy = $this->pdf->GetY();
		$this->pdf->SetLineStyle(array('width'=>0.2, 'cap'=>'butt', 'color'=>array(125,125,125)));

		$vehicule = new doliFleetVehicule($this->db);
		$vehicule->fetch($line->fk_vehicule);

		$default_font_size = pdf_getPDFFontSize($this->outputlangs);

		$posx = $this->marge_gauche;
		$this->pdf->SetXY($posx, $posy);
		$this->pdf->SetFont('', '', $default_font_size - 1);
		$str = $vehicule->immatriculation;
		$this->pdf->MultiCell($this->withForImmat, $this->h_ligne, $str, $borderStyle, 'L');

		$posx += $this->withForImmat;
		$this->pdf->SetXY($posx, $posy);
		$this->pdf->SetFont('', '', $default_font_size - 1);
		$str = dol_print_date($vehicule->date_customer_exploit);
		$this->pdf->MultiCell($this->widthForDateExploit, $this->h_ligne, $str, $borderStyle, 'L');

		$posx += $this->widthForDateExploit;
		$this->pdf->SetXY($posx, $posy);
		$this->pdf->SetFont('', '', $default_font_size - 1);
		$str = $line->description;
		$this->pdf->MultiCell($this->widthForDesc, $this->h_ligne, $str, $borderStyle, 'L');

		$posx += $this->widthForDesc;
		$this->pdf->SetXY($posx, $posy);
		$this->pdf->SetFont('', '', $default_font_size - 1);
		$str = price($line->total_ht);
		$this->pdf->MultiCell($this->widthForTotalHT, $this->h_ligne, $str, $borderStyle, 'R');
	}

	function printActivity($activityId)
	{
		$activityLabel = $this->dictTypeAct->getValueFromId($activityId);

		$posy = $this->pdf->GetY();
		$this->pdf->SetLineStyle(array('width'=>0.2, 'cap'=>'butt', 'color'=>array(125,125,125)));

		$default_font_size = pdf_getPDFFontSize($this->outputlangs);

		$this->pdf->SetXY($this->marge_gauche, $posy);
		$this->pdf->SetFont('', '', $default_font_size - 1);
		$str = $this->outputlangs->transnoentities('VehiculeActivities') . ' : ' . $activityLabel;
		$this->pdf->MultiCell($this->withForImmat+$this->widthForDateExploit+$this->widthForDesc, $this->h_ligne, $str, 'LRB', 'C');

		$posx = $this->marge_gauche + $this->withForImmat+$this->widthForDateExploit+$this->widthForDesc;

		$this->pdf->SetXY($posx, $posy);
		$this->pdf->MultiCell($this->widthForTotalHT, $this->h_ligne, price($this->subtotals[$activityId]['total']), 'LRB', 'R');

	}

	function printVehiculeType($activityId, $typeId)
	{
		$VtypeLabel = $this->dictTypeVeh->getValueFromId($typeId);

		$posy = $this->pdf->GetY();
		$this->pdf->SetLineStyle(array('width'=>0.2, 'cap'=>'butt', 'color'=>array(125,125,125)));

		$default_font_size = pdf_getPDFFontSize($this->outputlangs);

		$this->pdf->SetXY($this->marge_gauche, $posy);
		$this->pdf->SetFont('', '', $default_font_size - 1);
		$str = $VtypeLabel;
		$this->pdf->MultiCell($this->withForImmat+$this->widthForDateExploit+$this->widthForDesc, $this->h_ligne, $str, 1, 'C');

		$posx = $this->marge_gauche + $this->withForImmat+$this->widthForDateExploit+$this->widthForDesc;

		$this->pdf->SetXY($posx, $posy);
		$this->pdf->MultiCell($this->widthForTotalHT, $this->h_ligne, price($this->subtotals[$activityId][$typeId]), 1, 'R');
	}


	/**
	 *  Show top header of page.
	 *
	 *  @param	PDF			$pdf     		Object PDF
	 *  @param  Object		$object     	Object to show
	 *  @param  int	    	$showaddress    0=no, 1=yes
	 *  @param  Translate	$outputlangs	Object lang for output
	 *  @return	void
	 */
	function _pagehead(&$pdf, $object, $showaddress, $outputlangs, $titlekey = "dolifleetRentalProposal")
	{
		// phpcs:enable
		global $conf,$langs,$hookmanager;

		// Load traductions files required by page
		$outputlangs->loadLangs(array("main", "bills", "propal", "orders", "companies"));

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		pdf_pagehead($this->pdf, $outputlangs, $this->page_hauteur);

		// Show Draft Watermark
		if($object->statut==0 && (! empty($conf->global->COMMANDE_DRAFT_WATERMARK)) )
		{
			pdf_watermark($this->pdf, $outputlangs, $this->page_hauteur, $this->page_largeur, 'mm', $conf->global->COMMANDE_DRAFT_WATERMARK);
		}

		$this->pdf->SetTextColor(0, 0, 60);
		$this->pdf->SetFont('', 'B', $default_font_size + 3);

		$posy=$this->marge_haute;
		$posx=$this->page_largeur-$this->marge_droite-100;

		$this->pdf->SetXY($this->marge_gauche, $posy);

		// Logo
		if (empty($conf->global->PDF_DISABLE_MYCOMPANY_LOGO))
		{
			if ($this->emetteur->logo)
			{
				$logodir = $conf->mycompany->dir_output;
				if (! empty($conf->mycompany->multidir_output[$object->entity])) $logodir = $conf->mycompany->multidir_output[$object->entity];
				if (empty($conf->global->MAIN_PDF_USE_LARGE_LOGO))
				{
					$logo = $logodir.'/logos/thumbs/'.$this->emetteur->logo_small;
				}
				else {
					$logo = $logodir.'/logos/'.$this->emetteur->logo;
				}
				if (is_readable($logo))
				{
					$height=pdf_getHeightForLogo($logo);
					$this->pdf->Image($logo, $this->marge_gauche, $posy, 0, $height);	// width=0 (auto)
				}
				else
				{
					$this->pdf->SetTextColor(200, 0, 0);
					$this->pdf->SetFont('', 'B', $default_font_size -2);
					$this->pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorLogoFileNotFound", $logo), 0, 'L');
					$this->pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorGoToGlobalSetup"), 0, 'L');
				}
			}
			else
			{
				$text=$this->emetteur->name;
				$this->pdf->MultiCell(100, 4, $outputlangs->convToOutputCharset($text), 0, 'L');
			}
		}

		$this->pdf->SetFont('', 'B', $default_font_size + 3);
		$this->pdf->SetXY($posx, $posy);
		$this->pdf->SetTextColor(0, 0, 60);
		$title=$outputlangs->transnoentities($titlekey);
		$this->pdf->MultiCell(100, 3, $title, '', 'R');

		$this->pdf->SetFont('', 'B', $default_font_size);

		$posy+=5;
		$this->pdf->SetXY($posx, $posy);
		$this->pdf->SetTextColor(0, 0, 60);
		$this->pdf->MultiCell(100, 4, $outputlangs->transnoentities("Ref")." : " . $outputlangs->convToOutputCharset($object->ref), '', 'R');

		$posy+=1;
		$this->pdf->SetFont('', '', $default_font_size - 1);

		if ($object->ref_client)
		{
			$posy+=5;
			$this->pdf->SetXY($posx, $posy);
			$this->pdf->SetTextColor(0, 0, 60);
			$this->pdf->MultiCell(100, 3, $outputlangs->transnoentities("RefCustomer")." : " . $outputlangs->convToOutputCharset($object->ref_client), '', 'R');
		}

		$posy+=4;
		$this->pdf->SetXY($posx, $posy);
		$this->pdf->SetTextColor(0, 0, 60);
		$montharray = monthArray($outputlangs, 1);
		$this->pdf->MultiCell(100, 3, $outputlangs->transnoentities("Period")." : " . $montharray[$object->month] . " " . $object->year, '', 'R');

		if (!empty($conf->global->DOC_SHOW_CUSTOMER_CODE) && ! empty($object->thirdparty->code_client))
		{
			$posy+=4;
			$this->pdf->SetXY($posx, $posy);
			$this->pdf->SetTextColor(0, 0, 60);
			$this->pdf->MultiCell(100, 3, $outputlangs->transnoentities("CustomerCode")." : " . $outputlangs->transnoentities($object->thirdparty->code_client), '', 'R');
		}

		// Get contact
		if (!empty($conf->global->DOC_SHOW_FIRST_SALES_REP))
		{
			$arrayidcontact=$object->getIdContact('internal', 'SALESREPFOLL');
			if (count($arrayidcontact) > 0)
			{
				$usertmp=new User($this->db);
				$usertmp->fetch($arrayidcontact[0]);
				$posy+=4;
				$this->pdf->SetXY($posx, $posy);
				$this->pdf->SetTextColor(0, 0, 60);
				$this->pdf->MultiCell(100, 3, $langs->trans("SalesRepresentative")." : ".$usertmp->getFullName($langs), '', 'R');
			}
		}

		$posy+=2;

		$top_shift = 0;
		// Show list of linked objects
		$current_y = $this->pdf->getY();
		$posy = pdf_writeLinkedObjects($this->pdf, $object, $outputlangs, $posx, $posy, 100, 3, 'R', $default_font_size);
		if ($current_y < $this->pdf->getY())
		{
			$top_shift = $this->pdf->getY() - $current_y;
		}

		if ($showaddress)
		{
			// Sender properties
			$carac_emetteur='';
			// Add internal contact of proposal if defined
			$arrayidcontact=$object->getIdContact('internal', 'SALESREPFOLL');
			if (count($arrayidcontact) > 0)
			{
				$object->fetch_user($arrayidcontact[0]);
				$labelbeforecontactname=($outputlangs->transnoentities("FromContactName")!='FromContactName'?$outputlangs->transnoentities("FromContactName"):$outputlangs->transnoentities("Name"));
				$carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$labelbeforecontactname." ".$outputlangs->convToOutputCharset($object->user->getFullName($outputlangs))."\n";
			}

			$carac_emetteur .= pdf_build_address($outputlangs, $this->emetteur, $object->thirdparty, '', 0, 'source', $object);

			// Show sender
			$posy=42+$top_shift;
			$posx=$this->marge_gauche;
			if (! empty($conf->global->MAIN_INVERT_SENDER_RECIPIENT)) $posx=$this->page_largeur-$this->marge_droite-80;
			$hautcadre=40;

			$afterHeader = $posy + $hautcadre;

			// Show sender frame
			$this->pdf->SetTextColor(0, 0, 0);
			$this->pdf->SetFont('', '', $default_font_size - 2);
			$this->pdf->SetXY($posx, $posy-5);
			$this->pdf->MultiCell(66, 5, $outputlangs->transnoentities("BillFrom").":", 0, 'L');
			$this->pdf->SetXY($posx, $posy);
			$this->pdf->SetFillColor(230, 230, 230);
			$this->pdf->MultiCell(82, $hautcadre, "", 0, 'R', 1);
			$this->pdf->SetTextColor(0, 0, 60);

			// Show sender name
			$this->pdf->SetXY($posx+2, $posy+3);
			$this->pdf->SetFont('', 'B', $default_font_size);
			$this->pdf->MultiCell(80, 4, $outputlangs->convToOutputCharset($this->emetteur->name), 0, 'L');
			$posy=$this->pdf->getY();

			// Show sender information
			$this->pdf->SetXY($posx+2, $posy);
			$this->pdf->SetFont('', '', $default_font_size - 1);
			$this->pdf->MultiCell(80, 4, $carac_emetteur, 0, 'L');



			// If CUSTOMER contact defined on order, we use it
			$usecontact=false;
			$arrayidcontact=$object->getIdContact('external', 'CUSTOMER');
			if (count($arrayidcontact) > 0)
			{
				$usecontact=true;
				$result=$object->fetch_contact($arrayidcontact[0]);
			}

			//Recipient name
			// On peut utiliser le nom de la societe du contact
			if ($usecontact && !empty($conf->global->MAIN_USE_COMPANY_NAME_OF_CONTACT)) {
				$thirdparty = $object->contact;
			} else {
				$thirdparty = $object->thirdparty;
			}

			$carac_client_name= pdfBuildThirdpartyName($thirdparty, $outputlangs);

			$carac_client=pdf_build_address($outputlangs, $this->emetteur, $object->thirdparty, ($usecontact?$object->contact:''), $usecontact, 'target', $object);

			// Show recipient
			$widthrecbox=100;
			if ($this->page_largeur < 210) $widthrecbox=84;	// To work with US executive format
			$posy=42+$top_shift;
			$posx=$this->page_largeur-$this->marge_droite-$widthrecbox;
			if (! empty($conf->global->MAIN_INVERT_SENDER_RECIPIENT)) $posx=$this->marge_gauche;

			// Show recipient frame
			$this->pdf->SetTextColor(0, 0, 0);
			$this->pdf->SetFont('', '', $default_font_size - 2);
			$this->pdf->SetXY($posx+2, $posy-5);
			$this->pdf->MultiCell($widthrecbox, 5, $outputlangs->transnoentities("BillTo").":", 0, 'L');
			$this->pdf->Rect($posx, $posy, $widthrecbox, $hautcadre);

			// Show recipient name
			$this->pdf->SetXY($posx+2, $posy+3);
			$this->pdf->SetFont('', 'B', $default_font_size);
			$this->pdf->MultiCell($widthrecbox, 4, $carac_client_name, 0, 'L');

			$posy = $this->pdf->getY();

			// Show recipient information
			$this->pdf->SetFont('', '', $default_font_size - 1);
			$this->pdf->SetXY($posx+2, $posy);
			$this->pdf->MultiCell($widthrecbox, 4, $carac_client, 0, 'L');
		}

		$this->pdf->SetTextColor(0, 0, 0);
		$this->pdf->SetY($afterHeader);

		return $afterHeader + 5;
	}
	/**
	 *   	Show footer of page. Need this->emetteur object
	 *
	 *   	@param	PDF			$pdf     			PDF
	 * 		@param	Object		$object				Object to show
	 *      @param	Translate	$outputlangs		Object lang for output
	 *      @param	int			$hidefreetext		1=Hide free text
	 *      @return	int								Return height of bottom margin including footer text
	 */
	function _pagefoot(&$pdf,$object,$outputlangs,$hidefreetext=0)
	{
		global $conf;
		$showdetails=$conf->global->MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS;
		return pdf_pagefoot($this->pdf,$outputlangs,'SHIPPING_FREE_TEXT',$this->emetteur,$this->marge_basse,$this->marge_gauche,$this->page_hauteur,$object,$showdetails,$hidefreetext);
	}

	/**
	 * A convenient method for PDF pagebreak
	 *
	 * @param 	TCPDF 	$pdf TCPDF object, this is also passed as first parameter of $callback function
	 * @param 	callable $callback a  callable callback function
	 * @param 	bool 	$autoPageBreak enable page jump
	 * @param 	array 	$param this is passed to seccond parametter of $callback function
	 * @return 	float 	Y position
	 */
	public function pdfPrintCallback(&$pdf, callable $callback, $autoPageBreak = true, $param = array())
	{
		global $conf, $outputlangs;

		$posY = $posYBefore = $pdf->GetY();

		if (is_callable($callback))
		{
			$pdf->startTransaction();
			$pageposBefore=$pdf->getPage();

			// START FIRST TRY
			$res = call_user_func_array($callback, array(&$pdf, $param));
			$pageposAfter=$pdf->getPage();
			$posY = $posYAfter = $pdf->GetY();
			// END FIRST TRY

			if($autoPageBreak && $pageposAfter > $pageposBefore )
			{
				$pagenb = $pageposBefore;
				$pdf->rollbackTransaction(true);
				$posY = $posYBefore;
				// prepare pages to receive content
				while ($pagenb < $pageposAfter) {
					$pdf->AddPage();
					$pagenb++;
					$this->prepareNewPage($pdf);
				}
				// BACK TO START
				$pdf->setPage($pageposBefore);
				$pdf->SetY($posYBefore);
				// RESTART DISPLAY BLOCK - without auto page break
				$posY = $this->pdfPrintCallback($pdf, $callback, false, $param);
			}
			else // No pagebreak
			{
				$pdf->commitTransaction();
			}
		}

		return $posY;
	}

	/**
	 * Prepare new page with header, footer, margin ...
	 * @param TCPDF $pdf
	 * @return float Y position
	 */
	public function prepareNewPage(&$pdf, $forceHead = false)
	{
		global $conf, $outputlangs;

		// Set path to the background PDF File
		if (! empty($conf->global->MAIN_ADD_PDF_BACKGROUND))
		{
			$pagecount = $pdf->setSourceFile($conf->mycompany->dir_output.'/'.$conf->global->MAIN_ADD_PDF_BACKGROUND);
			$tplidx = $pdf->importPage(1);
		}

		if (! empty($tplidx)) $pdf->useTemplate($tplidx);

		if ($forceHead || empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) $afterHeader = $this->_pagehead($pdf, $this->object, 1, $outputlangs);
		$this->lineTableHeader($pdf, $outputlangs);

		$topY = $pdf->GetY() + 20;
		$pdf->SetMargins($this->marge_gauche, $topY, $this->marge_droite); // Left, Top, Right

		$pdf->SetAutoPageBreak(0, 0); // to prevent footer creating page
		$this->heightForFooter = $this->_pagefoot($pdf,$this->object, $outputlangs);
		$pdf->SetAutoPageBreak(1, $this->heightForFooter);

		// The only function to edit the bottom margin of current page to set it.
		$pdf->setPageOrientation('', 1, $this->heightForFooter);

		$tab_top_newpage = $afterHeader + $this->h_ligne;
		$pdf->SetY($tab_top_newpage);
		return empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)?42:10;
	}

	/**
	 * @param TCPDF $pdf
	 * @param string $outputlangs
	 */
	public function lineTableHeader(&$pdf, $outputlangs = '')
	{
		$posy = $this->pdf->GetY() + 5;

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		$this->pdf->SetXY($this->marge_gauche, $posy);
		$this->pdf->SetFont('', '', $default_font_size - 1);
		$this->pdf->MultiCell($this->withForImmat, $this->h_ligne, $outputlangs->trans("Immatriculation"), 1, 'L');

		$this->pdf->SetXY($this->marge_gauche + $this->withForImmat, $posy);
		$this->pdf->SetFont('', '', $default_font_size - 1);
		$this->pdf->MultiCell($this->widthForDateExploit, $this->h_ligne, $outputlangs->trans("Exploitation"), 1, 'L');

		$this->pdf->SetXY($this->marge_gauche + $this->withForImmat + $this->widthForDateExploit, $posy);
		$this->pdf->SetFont('', '', $default_font_size - 1);
		$this->pdf->MultiCell($this->widthForDesc, $this->h_ligne, $outputlangs->trans("Description"), 1, 'L');

		$this->pdf->SetXY($this->marge_gauche + $this->withForImmat + $this->widthForDateExploit + $this->widthForDesc, $posy);
		$this->pdf->SetFont('', '', $default_font_size - 1);
		$this->pdf->MultiCell($this->widthForTotalHT, $this->h_ligne, $outputlangs->trans("TotalHT"), 1, 'R');

	}

}

