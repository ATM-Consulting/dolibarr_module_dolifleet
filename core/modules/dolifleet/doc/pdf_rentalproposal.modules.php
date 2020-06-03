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

	public $maxImages4StepsLines = 3;

	public $maxImagesHeight4StepsLines = 80;
	public $ImagesGutter4StepsLines = 5;

	public $dictTypeAct;
	public $dictTypeVeh;

	public $heightForFooter;

	public $withForImmat = 30;
	public $widthForDateExploit = 40;
	public $widthForDesc = 80;
	public $widthForTotalHT = 40;

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

		if (! is_object($outputlangs)) $outputlangs=$langs;
		// For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
		if (! empty($conf->global->MAIN_USE_FPDF)) $outputlangs->charset_output='ISO-8859-1';

		// Translations
		$outputlangs->loadLangs(array("main", "bills", "products", "dict", "companies", "propal", "deliveries", "sendings", "productbatch"));

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
				$parameters=array('file'=>$file,'object'=>$object,'outputlangs'=>$outputlangs);
				global $action;
				$reshook=$hookmanager->executeHooks('beforePDFCreation',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks

				// Set nblignes with the new object lines content after hook
				$nblignes = 0 ;
				if(!empty($object->lines)){
					$nblignes = count($object->lines);
				}

				$pdf=pdf_getInstance($this->format);
				$this->default_font_size = pdf_getPDFFontSize($outputlangs);
				$heightforinfotot = 8;	// Height reserved to output the info and total part
				$heightforfreetext= (isset($conf->global->MAIN_PDF_FREETEXT_HEIGHT)?$conf->global->MAIN_PDF_FREETEXT_HEIGHT:5);	// Height reserved to output the free text on last page
				$heightforfooter = $this->marge_basse + 20;	// Height reserved to output the footer (value include bottom margin)
				if ($conf->global->MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS >0) $heightforfooter+= 6;
				$pdf->SetAutoPageBreak(1,0);

				if (class_exists('TCPDF'))
				{
					$pdf->setPrintHeader(false);
					$pdf->setPrintFooter(false);
				}
				$pdf->SetFont(pdf_getPDFFont($outputlangs));
				// Set path to the background PDF File
				if (! empty($conf->global->MAIN_ADD_PDF_BACKGROUND))
				{
					$pagecount = $pdf->setSourceFile($conf->mycompany->dir_output.'/'.$conf->global->MAIN_ADD_PDF_BACKGROUND);
					$tplidx = $pdf->importPage(1);
				}

				$pdf->Open();
				$pagenb=0;
				$pdf->SetDrawColor(128,128,128);

				if (method_exists($pdf,'AliasNbPages')) $pdf->AliasNbPages();

				$pdf->SetTitle($outputlangs->convToOutputCharset($object->ref));
				$pdf->SetSubject($outputlangs->transnoentities("Processrules"));
				$pdf->SetCreator("Dolibarr ".DOL_VERSION);
				$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
				$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref)." ".$outputlangs->transnoentities("Processrules"));
				if (! empty($conf->global->MAIN_DISABLE_PDF_COMPRESSION)) $pdf->SetCompression(false);

				$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right

				// New page
				$pdf->AddPage();
				$curentY = $this->prepareNewPage($pdf, true);


//				// Display Notes
//				$displayNoteParam = array('object' => $object, 'y' => $curentY);
//				$displayNoteMethod = array($this, 'displayNote');
//				$curentY = $this->pdfPrintCallback($pdf, $displayNoteMethod, true, $displayNoteParam);
//
//				// Display Description
//				$displayParam = array('object' => $object, 'y' => $curentY);
//				$curentY = $this->pdfPrintCallback($pdf, array($this, 'displayDescription'), true, $displayParam);

				$this->total_ht = 0;
				$this->subtotals = array();
				$typeAct = $typeVeh = 0;

				// Loop on each  procedure
				if(!empty($object->lines)){

					foreach ($object->lines as $l) {
						$this->total_ht+= $l->total_ht;
						$this->subtotals[$l->activity_type]['total'] += $l->total_ht;
						$this->subtotals[$l->activity_type][$l->fk_vehicule_type] += $l->total_ht;
					}

					foreach ($object->lines as $line)
					{
						/*
						 ce qu'il y a dans la card pour les titres avec le total affiché

						if ($typeAct !== $line->activity_type)
					{
						$activityLabel = $dictTypeAct->getValueFromId($line->activity_type);
						print '<tr>';
						print '<td colspan="3" align="center" style="background-color: #adadad">';
						print $activityLabel;
						print '</td>';
						print '<td class="linecolht right" style="background-color: #adadad">'.$langs->trans('Total').' '.$activityLabel.' : '.price($subtotals[$line->activity_type]['total']).'</td>';
						print '<td style="background-color: #adadad"></td>';
						print '</tr>';
						$typeAct = $line->activity_type;
						$typeVeh = 0;
					}

					if ($typeVeh !== $line->fk_vehicule_type)
					{
						$VtypeLabel = $dictTypeVeh->getValueFromId($line->fk_vehicule_type);
						print '<tr><td colspan="3" align="center" style="background-color: #d4d4d4">';
						print $VtypeLabel;
						print '</td>';
						print '<td class="linecolht right" style="background-color: #d4d4d4">'.$langs->trans('Total').' '.$VtypeLabel.' : '.price($subtotals[$line->activity_type][$line->fk_vehicule_type]).'</td>';
						print '<td style="background-color: #d4d4d4"></td>';
						print '</tr>';
						$typeVeh = $line->fk_vehicule_type;
					}

						 */
					}

//					foreach ($object->lines as $procedure)
//					{
//						/**
//						 * @var $procedure Procedure
//						 */
//
//						$curentY + 6;
//
//						$displayParam = array(
//							'y' => $curentY,
//							'object' => $object,
//							'procedure' => $procedure
//						);
//
//						$curentY = $this->pdfPrintCallback($pdf, array($this, 'displayProcedure'), true, $displayParam);
//
//						$curentY + 6;
//
//						$procedure->fetch_lines();
//
//						// Loop on each step
//						if(!empty($procedure->lines)){
//							foreach ($procedure->lines as $step)
//							{
//								/**
//								 * @var $step ProcessStep
//								 */
//
//								$displayParam = array(
//									'y' => $curentY,
//									'object' => $object,
//									'procedure' => $procedure,
//									'step' => $step
//								);
//
//								$curentY = $this->pdfPrintCallback($pdf, array($this, 'displayStep'), true, $displayParam);
//
//								$TImage = $step->fetch_images();
//
//								// Le plus simple c'est de gérer les photos par une liste de lignes pour faciliter les sauts de pages
//								$TImageMatrix = $this->prepareImagesMatrix($TImage);
//
//								if(!empty($TImageMatrix))
//								{
//									foreach ($TImageMatrix as $matrixLine)
//									{
//										$col = 0; // init du numero de photo
//
//										if(!empty($matrixLine['TImage']))
//										{
//											$curentY+= $this->ImagesGutter4StepsLines;
//
//											// If photo too high, we moved completely on new page
//											if (($curentY + $matrixLine['lineHeight']) > $this->page_hauteur - $heightforfooter )
//											{
//												$pdf->AddPage();
//												$curentY = $this->prepareNewPage($pdf);
//											}
//
//											foreach ($matrixLine['TImage'] as $image)
//											{
//												// Calcule de la position
//												$x = $this->marge_gauche + $col * ($matrixLine['colWidth'] + $this->ImagesGutter4StepsLines);
//
//												// Centrage horizontale
//												$offsetX = round(($matrixLine['colWidth'] - $image->width) / 2 , 2);
//
//												// centrage verticale
//												$offsetY = round(($matrixLine['lineHeight'] - $image->height) / 2 , 2);
//
//												if($image->deg > 0)
//												{
//													// Start Transformation
//													$pdf->StartTransform();
//													$pdf->Rotate($image->deg, $x + $offsetX + $image->width/2, $curentY + $offsetY + $image->height / 2);
//												}
//
//
//												// Affichage de l'image
//												$pdf->Image(
//													$image->realFilePath,
//													$x + $offsetX,
//													$curentY + $offsetY,
//													$image->width,
//													$image->height,
//													'',
//													'',
//													'',
//													2,
//													150 // use 150 DPI to reduce PDF size
//												);
//
//												if($image->deg > 0)
//												{
//													// Stop Transformation
//													$pdf->StopTransform();
//												}
//
//												$col++;
//											}
//
//											$curentY+= $matrixLine['lineHeight'];
//										}
//									}
//								}
//							}
//
//							$curentY + 6;
//						}
//					}
				}


				// Pied de page
				if (method_exists($pdf,'AliasNbPages')) $pdf->AliasNbPages();

				$pdf->Close();

				$pdf->Output($file,'F');

				// Add pdfgeneration hook
				$hookmanager->initHooks(array('pdfgeneration'));
				$parameters=array('file'=>$file,'object'=>$object,'outputlangs'=>$outputlangs);
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


	/**
	 *  Show top header of page.
	 *
	 *  @param	PDF			$pdf     		Object PDF
	 *  @param  Object		$object     	Object to show
	 *  @param  int	    	$showaddress    0=no, 1=yes
	 *  @param  Translate	$outputlangs	Object lang for output
	 *  @return	void
	 */
//	function _pagehead(&$pdf, $object, $showaddress, $outputlangs)
//	{
//		global $conf,$langs,$mysoc;
//
//		$langs->load("orders");
//
//		$this->default_font_size = pdf_getPDFFontSize($outputlangs);
//
//		pdf_pagehead($pdf,$outputlangs,$this->page_hauteur);
//
//		// Show Draft Watermark
//		if($object->statut==0 && (! empty($conf->global->SHIPPING_DRAFT_WATERMARK)) )
//		{
//			pdf_watermark($pdf,$outputlangs,$this->page_hauteur,$this->page_largeur,'mm',$conf->global->SHIPPING_DRAFT_WATERMARK);
//		}
//
//		//Prepare la suite
//		$pdf->SetTextColor(0,0,60);
//		$pdf->SetFont('','B', $this->default_font_size + 3);
//
//		$w = 110;
//
//		$posy=$this->marge_haute;
//		$posx=$this->page_largeur-$this->marge_droite-$w;
//
//		$pdf->SetXY($this->marge_gauche,$posy);
//
//		// Logo
//		$logo=$conf->mycompany->dir_output.'/logos/'.$this->emetteur->logo;
//		if ($this->emetteur->logo)
//		{
//			if (is_readable($logo))
//			{
//				$height=pdf_getHeightForLogo($logo);
//				$pdf->Image($logo, $this->marge_gauche, $posy, 0, $height);	// width=0 (auto)
//			}
//			else
//			{
//				$pdf->SetTextColor(200,0,0);
//				$pdf->SetFont('','B', $this->default_font_size - 2);
//				$pdf->MultiCell($w, 3, $outputlangs->transnoentities("ErrorLogoFileNotFound",$logo), 0, 'L');
//				$pdf->MultiCell($w, 3, $outputlangs->transnoentities("ErrorGoToGlobalSetup"), 0, 'L');
//			}
//		}
//		else
//		{
//			$text=$this->emetteur->name;
//			$pdf->MultiCell($w, 4, $outputlangs->convToOutputCharset($text), 0, 'L');
//		}
//
//		// Show barcode
//		if (! empty($conf->barcode->enabled))
//		{
//			$posx=105;
//		}
//		else
//		{
//			$posx=$this->marge_gauche+3;
//		}
//
//
//
//		$posx=$this->page_largeur - $w - $this->marge_droite;
//		$posy=$this->marge_haute;
//
//		$pdf->SetFont('','B', $this->default_font_size + 2);
//		$pdf->SetXY($posx,$posy);
//		$pdf->SetTextColor(0,0,60);
//		$title=$outputlangs->transnoentities("PDFProcessruleTitle");
//		$pdf->MultiCell($w, 4, $title, '', 'R');
//
//		$pdf->SetFont('','', $this->default_font_size + 1);
//
//		$posy+=5;
//
//		$pdf->SetXY($posx,$posy);
//		$pdf->SetTextColor(0,0,60);
//		$pdf->MultiCell($w, 4, $outputlangs->transnoentities("PDFProcessruleRef") ." : ".$object->ref, '', 'R');
//
//
//		// reset to default font color ans size
//		$pdf->SetFont('','', $this->default_font_size + 3);
//		$pdf->SetTextColor(0,0,0);
//	}
	protected function _pagehead(&$pdf, $object, $showaddress, $outputlangs, $titlekey = "dolifleetRentalProposal")
	{
		// phpcs:enable
		global $conf,$langs,$hookmanager;

		// Load traductions files required by page
		$outputlangs->loadLangs(array("main", "bills", "propal", "orders", "companies"));

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		pdf_pagehead($pdf, $outputlangs, $this->page_hauteur);

		// Show Draft Watermark
		if($object->statut==0 && (! empty($conf->global->COMMANDE_DRAFT_WATERMARK)) )
		{
			pdf_watermark($pdf, $outputlangs, $this->page_hauteur, $this->page_largeur, 'mm', $conf->global->COMMANDE_DRAFT_WATERMARK);
		}

		$pdf->SetTextColor(0, 0, 60);
		$pdf->SetFont('', 'B', $default_font_size + 3);

		$posy=$this->marge_haute;
		$posx=$this->page_largeur-$this->marge_droite-100;

		$pdf->SetXY($this->marge_gauche, $posy);

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
					$pdf->Image($logo, $this->marge_gauche, $posy, 0, $height);	// width=0 (auto)
				}
				else
				{
					$pdf->SetTextColor(200, 0, 0);
					$pdf->SetFont('', 'B', $default_font_size -2);
					$pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorLogoFileNotFound", $logo), 0, 'L');
					$pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorGoToGlobalSetup"), 0, 'L');
				}
			}
			else
			{
				$text=$this->emetteur->name;
				$pdf->MultiCell(100, 4, $outputlangs->convToOutputCharset($text), 0, 'L');
			}
		}

		$pdf->SetFont('', 'B', $default_font_size + 3);
		$pdf->SetXY($posx, $posy);
		$pdf->SetTextColor(0, 0, 60);
		$title=$outputlangs->transnoentities($titlekey);
		$pdf->MultiCell(100, 3, $title, '', 'R');

		$pdf->SetFont('', 'B', $default_font_size);

		$posy+=5;
		$pdf->SetXY($posx, $posy);
		$pdf->SetTextColor(0, 0, 60);
		$pdf->MultiCell(100, 4, $outputlangs->transnoentities("Ref")." : " . $outputlangs->convToOutputCharset($object->ref), '', 'R');

		$posy+=1;
		$pdf->SetFont('', '', $default_font_size - 1);

		if ($object->ref_client)
		{
			$posy+=5;
			$pdf->SetXY($posx, $posy);
			$pdf->SetTextColor(0, 0, 60);
			$pdf->MultiCell(100, 3, $outputlangs->transnoentities("RefCustomer")." : " . $outputlangs->convToOutputCharset($object->ref_client), '', 'R');
		}

		$posy+=4;
		$pdf->SetXY($posx, $posy);
		$pdf->SetTextColor(0, 0, 60);
		$montharray = monthArray($outputlangs, 1);
		$pdf->MultiCell(100, 3, $outputlangs->transnoentities("Period")." : " . $montharray[$object->month] . " " . $object->year, '', 'R');

		if (!empty($conf->global->DOC_SHOW_CUSTOMER_CODE) && ! empty($object->thirdparty->code_client))
		{
			$posy+=4;
			$pdf->SetXY($posx, $posy);
			$pdf->SetTextColor(0, 0, 60);
			$pdf->MultiCell(100, 3, $outputlangs->transnoentities("CustomerCode")." : " . $outputlangs->transnoentities($object->thirdparty->code_client), '', 'R');
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
				$pdf->SetXY($posx, $posy);
				$pdf->SetTextColor(0, 0, 60);
				$pdf->MultiCell(100, 3, $langs->trans("SalesRepresentative")." : ".$usertmp->getFullName($langs), '', 'R');
			}
		}

		$posy+=2;

		$top_shift = 0;
		// Show list of linked objects
		$current_y = $pdf->getY();
		$posy = pdf_writeLinkedObjects($pdf, $object, $outputlangs, $posx, $posy, 100, 3, 'R', $default_font_size);
		if ($current_y < $pdf->getY())
		{
			$top_shift = $pdf->getY() - $current_y;
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

			// Show sender frame
			$pdf->SetTextColor(0, 0, 0);
			$pdf->SetFont('', '', $default_font_size - 2);
			$pdf->SetXY($posx, $posy-5);
			$pdf->MultiCell(66, 5, $outputlangs->transnoentities("BillFrom").":", 0, 'L');
			$pdf->SetXY($posx, $posy);
			$pdf->SetFillColor(230, 230, 230);
			$pdf->MultiCell(82, $hautcadre, "", 0, 'R', 1);
			$pdf->SetTextColor(0, 0, 60);

			// Show sender name
			$pdf->SetXY($posx+2, $posy+3);
			$pdf->SetFont('', 'B', $default_font_size);
			$pdf->MultiCell(80, 4, $outputlangs->convToOutputCharset($this->emetteur->name), 0, 'L');
			$posy=$pdf->getY();

			// Show sender information
			$pdf->SetXY($posx+2, $posy);
			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->MultiCell(80, 4, $carac_emetteur, 0, 'L');



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
			$pdf->SetTextColor(0, 0, 0);
			$pdf->SetFont('', '', $default_font_size - 2);
			$pdf->SetXY($posx+2, $posy-5);
			$pdf->MultiCell($widthrecbox, 5, $outputlangs->transnoentities("BillTo").":", 0, 'L');
			$pdf->Rect($posx, $posy, $widthrecbox, $hautcadre);

			// Show recipient name
			$pdf->SetXY($posx+2, $posy+3);
			$pdf->SetFont('', 'B', $default_font_size);
			$pdf->MultiCell($widthrecbox, 4, $carac_client_name, 0, 'L');

			$posy = $pdf->getY();

			// Show recipient information
			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->SetXY($posx+2, $posy);
			$pdf->MultiCell($widthrecbox, 4, $carac_client, 0, 'L');
		}

		$pdf->SetTextColor(0, 0, 0);
		return $top_shift;
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
		return pdf_pagefoot($pdf,$outputlangs,'SHIPPING_FREE_TEXT',$this->emetteur,$this->marge_basse,$this->marge_gauche,$this->page_hauteur,$object,$showdetails,$hidefreetext);
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

		if ($forceHead || empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) $this->_pagehead($pdf, $this->object, 1, $outputlangs);
		$this->lineTableHeader($pdf, $outputlangs);

		$topY = $pdf->GetY() + 20;
		$pdf->SetMargins($this->marge_gauche, $topY, $this->marge_droite); // Left, Top, Right

		$pdf->SetAutoPageBreak(0, 0); // to prevent footer creating page
		$this->heightForFooter = $this->_pagefoot($pdf,$this->object, $outputlangs);
		$pdf->SetAutoPageBreak(1, $this->heightForFooter);

		// The only function to edit the bottom margin of current page to set it.
		$pdf->setPageOrientation('', 1, $this->heightForFooter);

		$tab_top_newpage = (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)?42:10);
		$pdf->SetY($tab_top_newpage);
		return empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)?42:10;
	}

	/**
	 * @param TCPDF $pdf
	 * @param string $outputlangs
	 */
	public function lineTableHeader(&$pdf, $outputlangs = '')
	{
		$posy = $pdf->GetY() + 25;

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		$pdf->SetXY($this->marge_gauche, $posy);
		$pdf->SetFont('', '', $default_font_size - 1);
		$pdf->MultiCell($this->withForImmat, 4, $outputlangs->trans("Immatriculation"), 1, 'L');

		$pdf->SetXY($this->marge_gauche + $this->withForImmat, $posy);
		$pdf->SetFont('', '', $default_font_size - 1);
		$pdf->MultiCell($this->widthForDateExploit, 4, $outputlangs->trans("Exploitation"), 1, 'L');

		$pdf->SetXY($this->marge_gauche + $this->withForImmat + $this->widthForDateExploit, $posy);
		$pdf->SetFont('', '', $default_font_size - 1);
		$pdf->MultiCell($this->widthForDesc, 4, $outputlangs->trans("Description"), 1, 'L');

		$pdf->SetXY($this->marge_gauche + $this->withForImmat + $this->widthForDateExploit + $this->widthForDesc, $posy);
		$pdf->SetFont('', '', $default_font_size - 1);
		$pdf->MultiCell($this->widthForTotalHT, 4, $outputlangs->trans("TotalHT"), 1, 'R');
	}


	/**
	 * @param TCPDF $pdf
	 * @param array $param
	 * @return float Y position
	 */
	public function displayNote($pdf, $param){
		$y = $param['y'];
		$object = $param['object'];

		if (! empty($object->note_public))
		{
			$pdf->SetFont('','', $this->default_font_size - 1);   // Dans boucle pour gerer multi-page
			$pdf->writeHTMLCell(190, 3, $this->marge_gauche+1, $y, dol_htmlentitiesbr($object->note_public), 0, 1);

			$nexY = $pdf->GetY();
			$height_note=$nexY-$y;

			// Rect prend une longueur en 3eme param
			$pdf->SetDrawColor(192,192,192);
			$pdf->Rect($this->marge_gauche, $y-1, $this->page_largeur-$this->marge_gauche-$this->marge_droite, $height_note+1);

			$y = $nexY+6;
			$pdf->SetY($y);
		}

		return $y;
	}


	/**
	 * @param TCPDF $pdf
	 * @param array $param
	 * @return float Y position
	 */
	public function displayDescription($pdf, $param){
		$y = $param['y'];
		$object = $param['object'];
		/**
		 * @var $object ProcessRules
		 */

		if (! empty($object->description))
		{

			$pdf->SetFont('','', $this->default_font_size - 1);   // Dans boucle pour gerer multi-page
			$pdf->writeHTMLCell(190, 3, $this->marge_gauche+1, $y, dol_htmlentitiesbr($object->description), 0, 1);


			$nexY = $pdf->GetY();
			$height_note=$nexY-$y;

			// Rect prend une longueur en 3eme param
			$pdf->SetDrawColor(192,192,192);
			$pdf->Rect($this->marge_gauche, $y-1, $this->page_largeur-$this->marge_gauche-$this->marge_droite, $height_note+1);

			$y = $nexY+6;
			$pdf->SetY($y);
		}

		return $y;
	}

	function displayProcedure($pdf, $param){
		global $langs;


		//($procedure, $htmlId='', $open = true, $editable = true)

		$y = $param['y'];
		$object = $param['object'];
		$procedure = $param['procedure'];

		$fullWidth = $this->page_largeur-$this->marge_gauche-$this->marge_droite;

		/**
		 * @var $procedure Procedure
		 * @var $pdf TCPDF
		 */

		$curY = $y;
		// Titre de la procedure
		$pdf->SetXY($this->marge_gauche, $curY+1);
		$pdf->SetFont('','B', $this->default_font_size + 5);
		$pdf->MultiCell($fullWidth, 3, strtoupper($procedure->getNom()) , 1, 'L');

		// Description
		$curY = $pdf->getY() + 2;

		if(!empty($procedure->description))
		{
			$this->resetDefaultFont($pdf);
			$pdf->writeHTMLCell($fullWidth, 3, $this->marge_gauche, $curY, dol_htmlentitiesbr($procedure->description), 0, 1);

			$curY = $pdf->getY();
			$pdf->setY($curY + 6);
		}

	}

	function displayStep($pdf, $param){
		global $langs;

		//($procedure, $htmlId='', $open = true, $editable = true)

		$y = $param['y'];
		$object = $param['object'];
		$procedure = $param['procedure'];
		$step = $param['step'];

		$fullWidth = $this->page_largeur-$this->marge_gauche-$this->marge_droite;

		/**
		 * @var $procedure Procedure
		 * @var $step ProcessStep
		 * @var $pdf TCPDF
		 */

		$curY = $y;
		// Titre de l'etape
		$pdf->SetXY($this->marge_gauche, $curY+1);
		$pdf->SetFont('','B', $this->default_font_size + 1);
		$pdf->MultiCell($fullWidth, 3, dol_html_entity_decode($step->getNom(), ENT_QUOTES) , 0, 'L');
		$this->resetDefaultFont($pdf);

		// Description
		$curY = $pdf->getY() + 2;

		if(!empty($step->description))
		{
			$this->resetDefaultFont($pdf);
			$pdf->writeHTMLCell($fullWidth, 3, $this->marge_gauche, $curY, dol_htmlentitiesbr($step->description), 0, 1);

			$curY = $pdf->getY();
			$pdf->setY($curY + 6);
		}

	}




	public function resetDefaultFont($pdf){
		$pdf->SetFont('','', $this->default_font_size - 1);
		$pdf->SetTextColor(0,0,0);
	}

	/**
	 * Return dimensions to use for images onto PDF checking that width and height are not higher than
	 * maximum (16x32 by default).
	 *
	 * @param	string		$realpath		Full path to photo file to use
	 * @param	double		$maxwidth		size in mm
	 * @param	double		$maxheight		size in mm
	 * @return	array						Height and width to use to output image (in pdf user unit, so mm)
	 */
	function pdf_getSizeForImage($realpath, $maxwidth, $maxheight)
	{
		global $conf;

		include_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
		$tmp=dol_getImageSize($realpath);
		$tmpWidth = $tmp['width'];
		$tmpHeight = $tmp['height'];

		$return = array(
			'width'=>0,
			'height'=>0,
			'deg' => 0
		);


		if(!empty($conf->global->MAIN_USE_EXIF_ROTATION))
		{
			$exif = @exif_read_data($realpath);

			if ($exif !== false) {
				$orientation = intval(@$exif['Orientation']);
				if (in_array($orientation, array(3, 6, 8))) {
					switch ($orientation) {
						case 3:
							$return['deg'] = 180;
							break;
						case 6:
							$return['deg'] = 270;
							break;
						case 8:
							$return['deg'] = 90;
							break;
						default:
							$return['deg'] = 0;
					}
				}
			}
		}

		if ($tmpHeight)
		{
			if($return['deg'] == 90 || $return['deg'] == 270)
			{
				$return['height']=(int) round($maxheight*$tmpHeight/$tmpWidth);	// I try to use maxheight
				if ($return['height'] > $maxwidth)	// Pb with maxheight, so i use maxwidth
				{
					$return['height']=$maxwidth;
					$return['width']=(int) round($maxwidth*$tmpWidth/$tmpHeight);
				}
				else	// No pb with maxheight
				{
					$return['width']=$maxheight;
				}
			}
			else
			{
				$return['width']=(int) round($maxheight*$tmpWidth/$tmpHeight);	// I try to use maxheight
				if ($return['width'] > $maxwidth)	// Pb with maxheight, so i use maxwidth
				{
					$return['width']=$maxwidth;
					$return['height']=(int) round($maxwidth*$tmpHeight/$tmpWidth);
				}
				else	// No pb with maxheight
				{
					$return['height']=$maxheight;
				}
			}
		}

		return $return;
	}

	/**
	 * Return a prepared array of images
	 *
	 * @param	array		$TImage		list of images
	 * @return	array
	 */
	function prepareImagesMatrix($TImage)
	{
		global $conf;

		if(empty($TImage)){
			return false;
		}

		$TImageMatrix = array();
		$imageLineNum = 0;

		$fullWidth = $this->page_largeur - $this->marge_gauche - $this->marge_droite;

		$totalGutterWidth = $this->ImagesGutter4StepsLines * ($this->maxImages4StepsLines - 1);
		$imageWidth = ($fullWidth - $totalGutterWidth) / $this->maxImages4StepsLines ;

		$defaultLineArray = array(
			'TImage' => array(),
			'lineHeight' => 0,
			'colWidth' => $imageWidth,
			'fullWidth' => $fullWidth
		);

		foreach($TImage as $image){
			$realFilePath = DOL_DATA_ROOT.'/'.$image->filepath.'/'.$image->filename;

			if(is_file($realFilePath)){

				// Ajouter la premiere ligne
				if(empty($TImageMatrix[$imageLineNum])){
					$TImageMatrix[$imageLineNum] = $defaultLineArray;
				}

				// Add new line if needed
				$imageLineCount = count($TImageMatrix[$imageLineNum]['TImage']);
				if($imageLineCount >= $this->maxImages4StepsLines)
				{
					$imageLineNum++;
					$TImageMatrix[$imageLineNum] = $defaultLineArray;
				}

				// Define size of image
				$imglinesize = $this->pdf_getSizeForImage($realFilePath, $imageWidth, $this->maxImagesHeight4StepsLines);

				if(!empty($imglinesize)){
					$TImageMatrix[$imageLineNum]['TImage'][$image->id] = $image;
					$TImageMatrix[$imageLineNum]['TImage'][$image->id]->realFilePath = $realFilePath;
					$TImageMatrix[$imageLineNum]['TImage'][$image->id]->width  = $imglinesize['width'];
					$TImageMatrix[$imageLineNum]['TImage'][$image->id]->height = $imglinesize['height'];
					$TImageMatrix[$imageLineNum]['TImage'][$image->id]->deg = $imglinesize['deg'];



					if($image->deg == 90 || $image->deg == 270){
						// Update line height
						// note after rotate the final height is image width
						$TImageMatrix[$imageLineNum]['lineHeight'] = max($TImageMatrix[$imageLineNum]['lineHeight'], $imglinesize['width']);
					}
					else{
						// Update line height
						$TImageMatrix[$imageLineNum]['lineHeight'] = max($TImageMatrix[$imageLineNum]['lineHeight'], $imglinesize['height']);
					}


				}
			}
		}


		return $TImageMatrix;
	}



}

