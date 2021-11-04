<?php
	if (!$_GET['id'] || !$_GET['mode'] || !$_GET['from_day'] || !$_GET['from_month'] || !$_GET['from_year'] || !$_GET['to_day'] || !$_GET['to_month'] || !$_GET['to_year']) {
		redirect('FourOhFour');
	} else {
		// Payroll Period
		$payrollPeriod = '';
		$fromDay = $_GET['from_day'];
		$fromMonth = $_GET['from_month'];
		$fromYear = $_GET['from_year'];
		$toDay = $_GET['to_day'];
		$toMonth = $_GET['to_month'];
		$toYear = $_GET['to_year'];

		// ~ search queries
		$fromMonthWithZeroes = $fromMonth;
		if ($fromMonth < 10) {
			$fromMonthWithZeroes = '0' . $fromMonth;
		}
		$fromDayWithZeroes = $fromDay;
		if ($fromDay < 10) {
			$fromDayWithZeroes = '0' . $fromDay;
		}
		$fromDate = $fromYear . '-' . $fromMonthWithZeroes . '-' . $fromDayWithZeroes;
		$fromMonthRaw = new DateTime($fromDate);
		$fromMonthConverted = $fromMonthRaw->format('m');
		$fromMonthShort = DateTime::createFromFormat('m', $fromMonthConverted)->format('M');
		$fromMonthConverted = DateTime::createFromFormat('m', $fromMonthConverted)->format('F');
		$toMonthWithZeroes = $toMonth;
		if ($toMonth < 10) {
			$toMonthWithZeroes = '0' . $toMonth;
		}
		$toDayWithZeroes = $toDay;
		if ($toDay < 10) {
			$toDayWithZeroes = '0' . $toDay;
		}
		$toDate = $toYear . '-' . $toMonthWithZeroes . '-' . $toDayWithZeroes;
		$toMonthRaw = new DateTime($toDate);
		$toMonthConverted = $toMonthRaw->format('m');
		$toMonthShort = DateTime::createFromFormat('m', $toMonthConverted)->format('M');
		$toMonthConverted = DateTime::createFromFormat('m', $toMonthConverted)->format('F');
		if ($fromYear == $toYear) {
			if ($fromMonth == $toMonth) {
				$payrollPeriod = $payrollPeriod . $fromMonthConverted;
				if ($fromDay == $toDay) {
					$payrollPeriod = $payrollPeriod . ' ' . $fromDay;
				} else {
					$payrollPeriod = $payrollPeriod . ' ' . $fromDay . '-' . $toDay;
				}
				$payrollPeriod = $payrollPeriod . ', ' . $fromYear;
				
			} else {
				$payrollPeriod = $payrollPeriod . $fromMonthShort . ' ' . $fromDay . ', ' . $fromYear . ' - ' . $toMonthShort . ' ' . $toDay . ', ' . $toYear;
			}
		} else {
			$payrollPeriod = $payrollPeriod . $fromMonthShort . ' ' . $fromDay . ', ' . $fromYear . ' - ' . $toMonthShort . ' ' . $toDay . ', ' . $toYear;
		}
		// Data
		$ApplicantID = $_GET['id'];
		$ModeRaw = $_GET['mode'];
		$ModeRaw = strtoupper($ModeRaw);
		switch($ModeRaw) {
			case 'WEEKLY':
				$Mode = 0;
				$ModeRaw = 'Weekly';
				$cutoffTaxDivider = 4;
				break;
			case 'SEMI-MONTHLY':
				$Mode = 1;
				$ModeRaw = 'Semi-monthly';
				$cutoffTaxDivider = 2;
				break;
			case 'MONTHLY':
				$Mode = 2;
				$ModeRaw = 'Monthly';
				$cutoffTaxDivider = 1;
				break;
			default:
				$Mode = 0;
				$ModeRaw = 'Weekly';
				$cutoffTaxDivider = 4;
				break;
		}
		$CheckApplicant = $this->Model_Selects->CheckApplicant($ApplicantID);
		if ($CheckApplicant->num_rows() > 0) {
			foreach($CheckApplicant->result_array() as $row) {
				// Fetch Client
				if ($row['ClientEmployed']) {
					$GetClientID = $this->Model_Selects->GetClientID($row['ClientEmployed']);
					if ($GetClientID->num_rows() > 0) {
						foreach($GetClientID->result_array() as $crow) {
							$ClientName = $crow['Name'];
						}
					} else {
						$ClientName = 'No Client';
					}
				} else {
					$ClientName = 'No Client';
				}
				if (strlen($ClientName) > 25) {
					$ClientName = substr($ClientName, 0, 25);
					$ClientName = $ClientName . '...';
				}
				// Name Handler
				$fullName = '';
				if ($row['LastName']) {
					$fullName = $fullName . strtoupper($row['LastName']) . ', ';
				} else {
					$fullName = $fullName . '[<i>No Last Name</i>], ';
				}
				if ($row['FirstName']) {
					$fullName = $fullName . $row['FirstName'] . ' ';
				} else {
					$fullName = $fullName . '[<i>No First Name</i>] ';
				}
				if ($row['MiddleName']) {
					$fullName = $fullName . $row['MiddleName'][0] . '.';
				} else {
					$fullName = $fullName . '[<i>No MI</i>].';
				}
				if ($row['NameExtension']) {
					$fullName = $fullName . ', ' . $row['NameExtension'];
				}
				if (strlen($fullName) > 60) {
					$fullName = substr($fullName, 0, 60);
					$fullName = $fullName . '...';
				}
				// Salary Rate
				$rate = number_format($row['SalaryExpected']);
				$rate = str_replace(',', '', $rate);
				$salaryType = $row['SalaryType'];
				// Salary Data
				// ~ regular
				$EarningsTotal = 0;
				$NightPremiumTotal = 0;
				$RegularHours = number_format($this->Model_Selects->GetPayslipRegularHours($ApplicantID, $Mode, $fromDate, $toDate), 2, '.', '');
				if ($RegularHours <= 0) {
					$RegularHours = '-';
				}
				// var_dump($this->db->last_query());
				$RegularGrossPay = number_format($this->Model_Selects->GetPayslipRegularGrossPay($ApplicantID, $Mode, $fromDate, $toDate), 2, '.', '');
				if ($RegularGrossPay <= 0) {
					$RegularGrossPay = '-';
				} else {
					$EarningsTotal = $EarningsTotal + $RegularGrossPay;
				}
				$RegularOvertime = number_format($this->Model_Selects->GetPayslipRegularOvertime($ApplicantID, $Mode, $fromDate, $toDate), 2, '.', '');
				if ($RegularOvertime <= 0) {
					$RegularOvertime = '-';
				}
				$RegularOvertimeGrossPay = number_format($this->Model_Selects->GetPayslipRegularOvertimeGrossPay($ApplicantID, $Mode, $fromDate, $toDate), 2, '.', '');
				if ($RegularOvertimeGrossPay <= 0) {
					$RegularOvertimeGrossPay = '-';
				} else {
					$EarningsTotal = $EarningsTotal + $RegularOvertimeGrossPay;
				}
				$RegularNightHours = number_format($this->Model_Selects->GetPayslipRegularNightHours($ApplicantID, $Mode, $fromDate, $toDate), 2, '.', '');
				if ($RegularNightHours <= 0) {
					$RegularNightHours = '-';
				}
				$RegularNPGrossPay = number_format($this->Model_Selects->GetPayslipRegularNPGrossPay($ApplicantID, $Mode, $fromDate, $toDate), 2, '.', '');
				if ($RegularNPGrossPay <= 0) {
					$RegularNPGrossPay = '-';
				} else {
					$NightPremiumTotal = $NightPremiumTotal + $RegularNPGrossPay;
				}
				$RegularNightOvertime = number_format($this->Model_Selects->GetPayslipRegularNightOvertime($ApplicantID, $Mode, $fromDate, $toDate), 2, '.', '');
				if ($RegularNightOvertime <= 0) {
					$RegularNightOvertime = '-';
				}
				$RegularNPOvertimeGrossPay = number_format($this->Model_Selects->GetPayslipRegularNPOvertimeGrossPay($ApplicantID, $Mode, $fromDate, $toDate), 2, '.', '');
				if ($RegularNPOvertimeGrossPay <= 0) {
					$RegularNPOvertimeGrossPay = '-';
				} else {
					$NightPremiumTotal = $NightPremiumTotal + $RegularNPOvertimeGrossPay;
				}
				// ~ rest day
				$RestDayHours = number_format($this->Model_Selects->GetPayslipRestDayHours($ApplicantID, $Mode, $fromDate, $toDate), 2, '.', '');
				if ($RestDayHours <= 0) {
					$RestDayHours = '-';
				}
				$RestDayGrossPay = number_format($this->Model_Selects->GetPayslipRestDayGrossPay($ApplicantID, $Mode, $fromDate, $toDate), 2, '.', '');
				if ($RestDayGrossPay <= 0) {
					$RestDayGrossPay = '-';
				} else {
					$EarningsTotal = $EarningsTotal + $RestDayGrossPay;
				}
				$RestDayOvertime = number_format($this->Model_Selects->GetPayslipRestDayOvertime($ApplicantID, $Mode, $fromDate, $toDate), 2, '.', '');
				if ($RestDayOvertime <= 0) {
					$RestDayOvertime = '-';
				}
				$RestDayOvertimeGrossPay = number_format($this->Model_Selects->GetPayslipRestDayOvertimeGrossPay($ApplicantID, $Mode, $fromDate, $toDate), 2, '.', '');
				if ($RestDayOvertimeGrossPay <= 0) {
					$RestDayOvertimeGrossPay = '-';
				} else {
					$EarningsTotal = $EarningsTotal + $RestDayOvertimeGrossPay;
				}
				$RestDayNightHours = number_format($this->Model_Selects->GetPayslipRestDayNightHours($ApplicantID, $Mode, $fromDate, $toDate), 2, '.', '');
				if ($RestDayNightHours <= 0) {
					$RestDayNightHours = '-';
				}
				$RestDayNPGrossPay = number_format($this->Model_Selects->GetPayslipRestDayNPGrossPay($ApplicantID, $Mode, $fromDate, $toDate), 2, '.', '');
				if ($RestDayNPGrossPay <= 0) {
					$RestDayNPGrossPay = '-';
				} else {
					$NightPremiumTotal = $NightPremiumTotal + $RestDayNPGrossPay;
				}
				$RestDayNightOvertime = number_format($this->Model_Selects->GetPayslipRestDayNightOvertime($ApplicantID, $Mode, $fromDate, $toDate), 2, '.', '');
				if ($RestDayNightOvertime <= 0) {
					$RestDayNightOvertime = '-';
				}
				$RestDayNPOvertimeGrossPay = number_format($this->Model_Selects->GetPayslipRestDayNPOvertimeGrossPay($ApplicantID, $Mode, $fromDate, $toDate), 2, '.', '');
				if ($RestDayNPOvertimeGrossPay <= 0) {
					$RestDayNPOvertimeGrossPay = '-';
				} else {
					$NightPremiumTotal = $NightPremiumTotal + $RestDayNPOvertimeGrossPay;
				}
				// ~ special holiday
				$SpecialHolidayHours = number_format($this->Model_Selects->GetPayslipSpecialHolidayHours($ApplicantID, $Mode, $fromDate, $toDate), 2, '.', '');
				if ($SpecialHolidayHours <= 0) {
					$SpecialHolidayHours = '-';
				}
				$SpecialHolidayGrossPay = number_format($this->Model_Selects->GetPayslipSpecialHolidayGrossPay($ApplicantID, $Mode, $fromDate, $toDate), 2, '.', '');
				if ($SpecialHolidayGrossPay <= 0) {
					$SpecialHolidayGrossPay = '-';
				} else {
					$EarningsTotal = $EarningsTotal + $SpecialHolidayGrossPay;
				}
				$SpecialHolidayOvertime = number_format($this->Model_Selects->GetPayslipSpecialHolidayOvertime($ApplicantID, $Mode, $fromDate, $toDate), 2, '.', '');
				if ($SpecialHolidayOvertime <= 0) {
					$SpecialHolidayOvertime = '-';
				}
				$SpecialHolidayOvertimeGrossPay = number_format($this->Model_Selects->GetPayslipSpecialHolidayOvertimeGrossPay($ApplicantID, $Mode, $fromDate, $toDate), 2, '.', '');
				if ($SpecialHolidayOvertimeGrossPay <= 0) {
					$SpecialHolidayOvertimeGrossPay = '-';
				} else {
					$EarningsTotal = $EarningsTotal + $SpecialHolidayOvertimeGrossPay;
				}
				$SpecialHolidayNightHours = number_format($this->Model_Selects->GetPayslipSpecialHolidayNightHours($ApplicantID, $Mode, $fromDate, $toDate), 2, '.', '');
				if ($SpecialHolidayNightHours <= 0) {
					$SpecialHolidayNightHours = '-';
				}
				$SpecialHolidayNPGrossPay = number_format($this->Model_Selects->GetPayslipSpecialHolidayNPGrossPay($ApplicantID, $Mode, $fromDate, $toDate), 2, '.', '');
				if ($SpecialHolidayNPGrossPay <= 0) {
					$SpecialHolidayNPGrossPay = '-';
				} else {
					$NightPremiumTotal = $NightPremiumTotal + $SpecialHolidayNPGrossPay;
				}
				$SpecialHolidayNightOvertime = number_format($this->Model_Selects->GetPayslipSpecialHolidayNightOvertime($ApplicantID, $Mode, $fromDate, $toDate), 2, '.', '');
				if ($SpecialHolidayNightOvertime <= 0) {
					$SpecialHolidayNightOvertime = '-';
				}
				$SpecialHolidayNPOvertimeGrossPay = number_format($this->Model_Selects->GetPayslipSpecialHolidayNPOvertimeGrossPay($ApplicantID, $Mode, $fromDate, $toDate), 2, '.', '');
				if ($SpecialHolidayNPOvertimeGrossPay <= 0) {
					$SpecialHolidayNPOvertimeGrossPay = '-';
				} else {
					$NightPremiumTotal = $NightPremiumTotal + $SpecialHolidayNPOvertimeGrossPay;
				}
				// ~ special holiday & rest day
				$SPHRDHours = number_format($this->Model_Selects->GetPayslipSPHRDHours($ApplicantID, $Mode, $fromDate, $toDate), 2, '.', '');
				if ($SPHRDHours <= 0) {
					$SPHRDHours = '-';
				}
				$SPHRDGrossPay = number_format($this->Model_Selects->GetPayslipSPHRDGrossPay($ApplicantID, $Mode, $fromDate, $toDate), 2, '.', '');
				if ($SPHRDGrossPay <= 0) {
					$SPHRDGrossPay = '-';
				} else {
					$EarningsTotal = $EarningsTotal + $SPHRDGrossPay;
				}
				$SPHRDOvertime = number_format($this->Model_Selects->GetPayslipSPHRDOvertime($ApplicantID, $Mode, $fromDate, $toDate), 2, '.', '');
				if ($SPHRDOvertime <= 0) {
					$SPHRDOvertime = '-';
				}
				$SPHRDOvertimeGrossPay = number_format($this->Model_Selects->GetPayslipSPHRDOvertimeGrossPay($ApplicantID, $Mode, $fromDate, $toDate), 2, '.', '');
				if ($SPHRDOvertimeGrossPay <= 0) {
					$SPHRDOvertimeGrossPay = '-';
				} else {
					$EarningsTotal = $EarningsTotal + $SPHRDOvertimeGrossPay;
				}
				$SPHRDNightHours = number_format($this->Model_Selects->GetPayslipSPHRDNightHours($ApplicantID, $Mode, $fromDate, $toDate), 2, '.', '');
				if ($SPHRDNightHours <= 0) {
					$SPHRDNightHours = '-';
				}
				$SPHRDNPGrossPay = number_format($this->Model_Selects->GetPayslipSPHRDNPGrossPay($ApplicantID, $Mode, $fromDate, $toDate), 2, '.', '');
				if ($SPHRDNPGrossPay <= 0) {
					$SPHRDNPGrossPay = '-';
				} else {
					$NightPremiumTotal = $NightPremiumTotal + $SPHRDNPGrossPay;
				}
				$SPHRDNightOvertime = number_format($this->Model_Selects->GetPayslipSPHRDNightOvertime($ApplicantID, $Mode, $fromDate, $toDate), 2, '.', '');
				if ($SPHRDNightOvertime <= 0) {
					$SPHRDNightOvertime = '-';
				}
				$SPHRDNPOvertimeGrossPay = number_format($this->Model_Selects->GetPayslipSPHRDNPOvertimeGrossPay($ApplicantID, $Mode, $fromDate, $toDate), 2, '.', '');
				if ($SPHRDNPOvertimeGrossPay <= 0) {
					$SPHRDNPOvertimeGrossPay = '-';
				} else {
					$NightPremiumTotal = $NightPremiumTotal + $SPHRDNPOvertimeGrossPay;
				}
				// ~ national holiday 100%
				$NHOHPHoursRaw = 0; // National Holiday One Hundred Percent Hours
				$NHOHPHours = 0;
				$NationalHolidayOneHundredHours = $this->Model_Selects->GetPayslipNationalHolidayOneHundredHours($ApplicantID, $Mode, $fromDate, $toDate);
				if ($NationalHolidayOneHundredHours->num_rows() > 0) {
					$NHOHPHoursRaw = $NationalHolidayOneHundredHours->num_rows();
					$NHOHPHours = number_format(($NHOHPHoursRaw * 8), 2, '.', '');
				}
				$NHOHPGrossPay = number_format(($NHOHPHoursRaw * $rate), 2, '.', '');
				if ($NHOHPHours <= 0) {
					$NHOHPHours = '-';
				}
				if ($NHOHPGrossPay <= 0) {
					$NHOHPGrossPay = '-';
				} else {
					$EarningsTotal = $EarningsTotal + $NHOHPGrossPay;
				}
				// ~ national holiday 200%
				$NationalHolidayHours = number_format($this->Model_Selects->GetPayslipNationalHolidayHours($ApplicantID, $Mode, $fromDate, $toDate), 2, '.', '');
				if ($NationalHolidayHours <= 0) {
					$NationalHolidayHours = '-';
				}
				$NationalHolidayGrossPay = number_format($this->Model_Selects->GetPayslipNationalHolidayGrossPay($ApplicantID, $Mode, $fromDate, $toDate), 2, '.', '');
				if ($NationalHolidayGrossPay <= 0) {
					$NationalHolidayGrossPay = '-';
				} else {
					$EarningsTotal = $EarningsTotal + $NationalHolidayGrossPay;
				}
				$NationalHolidayOvertime = number_format($this->Model_Selects->GetPayslipNationalHolidayOvertime($ApplicantID, $Mode, $fromDate, $toDate), 2, '.', '');
				if ($NationalHolidayOvertime <= 0) {
					$NationalHolidayOvertime = '-';
				}
				$NationalHolidayOvertimeGrossPay = number_format($this->Model_Selects->GetPayslipNationalHolidayOvertimeGrossPay($ApplicantID, $Mode, $fromDate, $toDate), 2, '.', '');
				if ($NationalHolidayOvertimeGrossPay <= 0) {
					$NationalHolidayOvertimeGrossPay = '-';
				} else {
					$EarningsTotal = $EarningsTotal + $NationalHolidayOvertimeGrossPay;
				}
				$NationalHolidayNightHours = number_format($this->Model_Selects->GetPayslipNationalHolidayNightHours($ApplicantID, $Mode, $fromDate, $toDate), 2, '.', '');
				if ($NationalHolidayNightHours <= 0) {
					$NationalHolidayNightHours = '-';
				}
				$NationalHolidayNPGrossPay = number_format($this->Model_Selects->GetPayslipNationalHolidayNPGrossPay($ApplicantID, $Mode, $fromDate, $toDate), 2, '.', '');
				if ($NationalHolidayNPGrossPay <= 0) {
					$NationalHolidayNPGrossPay = '-';
				} else {
					$NightPremiumTotal = $NightPremiumTotal + $NationalHolidayNPGrossPay;
				}
				$NationalHolidayNightOvertime = number_format($this->Model_Selects->GetPayslipNationalHolidayNightOvertime($ApplicantID, $Mode, $fromDate, $toDate), 2, '.', '');
				if ($NationalHolidayNightOvertime <= 0) {
					$NationalHolidayNightOvertime = '-';
				}
				$NationalHolidayNPOvertimeGrossPay = number_format($this->Model_Selects->GetPayslipNationalHolidayNPOvertimeGrossPay($ApplicantID, $Mode, $fromDate, $toDate), 2, '.', '');
				if ($NationalHolidayNPOvertimeGrossPay <= 0) {
					$NationalHolidayNPOvertimeGrossPay = '-';
				} else {
					$NightPremiumTotal = $NightPremiumTotal + $NationalHolidayNPOvertimeGrossPay;
				}
				// ~ national holiday & rest day
				$NHRDHours = number_format($this->Model_Selects->GetPayslipNHRDHours($ApplicantID, $Mode, $fromDate, $toDate), 2, '.', '');
				if ($NHRDHours <= 0) {
					$NHRDHours = '-';
				}
				$NHRDGrossPay = number_format($this->Model_Selects->GetPayslipNHRDGrossPay($ApplicantID, $Mode, $fromDate, $toDate), 2, '.', '');
				if ($NHRDGrossPay <= 0) {
					$NHRDGrossPay = '-';
				} else {
					$EarningsTotal = $EarningsTotal + $NHRDGrossPay;
				}
				$NHRDOvertime = number_format($this->Model_Selects->GetPayslipNHRDOvertime($ApplicantID, $Mode, $fromDate, $toDate), 2, '.', '');
				if ($NHRDOvertime <= 0) {
					$NHRDOvertime = '-';
				}
				$NHRDOvertimeGrossPay = number_format($this->Model_Selects->GetPayslipNHRDOvertimeGrossPay($ApplicantID, $Mode, $fromDate, $toDate), 2, '.', '');
				if ($NHRDOvertimeGrossPay <= 0) {
					$NHRDOvertimeGrossPay = '-';
				} else {
					$EarningsTotal = $EarningsTotal + $NHRDOvertimeGrossPay;
				}
				$NHRDNightHours = number_format($this->Model_Selects->GetPayslipNHRDNightHours($ApplicantID, $Mode, $fromDate, $toDate), 2, '.', '');
				if ($NHRDNightHours <= 0) {
					$NHRDNightHours = '-';
				}
				$NHRDNPGrossPay = number_format($this->Model_Selects->GetPayslipNHRDNPGrossPay($ApplicantID, $Mode, $fromDate, $toDate), 2, '.', '');
				if ($NHRDNPGrossPay <= 0) {
					$NHRDNPGrossPay = '-';
				} else {
					$NightPremiumTotal = $NightPremiumTotal + $NHRDNPGrossPay;
				}
				$NHRDNightOvertime = number_format($this->Model_Selects->GetPayslipNHRDNightOvertime($ApplicantID, $Mode, $fromDate, $toDate), 2, '.', '');
				if ($NHRDNightOvertime <= 0) {
					$NHRDNightOvertime = '-';
				}
				$NHRDNPOvertimeGrossPay = number_format($this->Model_Selects->GetPayslipNHRDNPOvertimeGrossPay($ApplicantID, $Mode, $fromDate, $toDate), 2, '.', '');
				if ($NHRDNPOvertimeGrossPay <= 0) {
					$NHRDNPOvertimeGrossPay = '-';
				} else {
					$NightPremiumTotal = $NightPremiumTotal + $NHRDNPOvertimeGrossPay;
				}
				$SSSAmountPaidTotal = $this->Model_Selects->GetSSSWeekPaidTotalBetweenPeriod($ApplicantID, $Mode, $fromDate, $toDate);
				$GrossPayTotal = $EarningsTotal + $NightPremiumTotal + $SSSAmountPaidTotal;
				if ($GrossPayTotal < 0) {
					$GrossPayTotal = 0;
				}
				// ~ deductions
				$sssTable = $this->Model_Selects->GetAllSSSTable();
				$hdmfTable = $this->Model_Selects->GetAllHDMFTable();
				$philhealthTable = $this->Model_Selects->GetAllPhilHealthTable();
				$SSSTotal = 0;
				$hdmf_rate = 0;
				$GetClientSalaryConfig = $this->Model_Selects->GetClientSalaryConfig($row['ClientEmployed']);
				$hdmfFrom = 0;
				$hdmfTo = 0;
				$sssFrom = 0;
				$sssTo = 0;
				if ($GetClientSalaryConfig->num_rows() > 0) {
					foreach($GetClientSalaryConfig->result_array() as $gcscrow) {
						$sssFrom = $gcscrow['SSSDayFrom'];
						$sssTo = $gcscrow['SSSDayTo'];
						$hdmfFrom = $gcscrow['HDMFDayFrom'];
						$hdmfTo = $gcscrow['HDMFDayTo'];
					}
				}
				foreach ($sssTable->result_array() as $srow) {
					if (($fromDay >= $sssFrom && $fromDay <= $sssTo) || ($toDay >= $sssFrom && $toDay <= $sssTo)) {
						if ($GrossPayTotal >= $srow['f_range'] && $GrossPayTotal <= $srow['t_range']) {
							$SSSTotal = $srow['contribution_ee'];
							// echo ($fromDay >= $sssFrom && $fromDay <= $sssTo);
 						}
					}
				}
				foreach ($hdmfTable->result_array() as $hrow) {
					if (($fromDay >= $hdmfFrom && $fromDay <= $hdmfTo) || ($toDay >= $hdmfFrom && $toDay <= $hdmfTo)) {
						if ($GrossPayTotal >= $hrow['f_range'] && $GrossPayTotal <= $hrow['t_range']) {
							$hdmf_rate = $hrow['contribution_ee'];
						}
					}
				}
				$philhealthArray=$philhealthTable->result_array();
				if ($GrossPayTotal >= $philhealthArray[0]['f_range'] && $GrossPayTotal <= $philhealthArray[0]['t_range']) {
					$philhealth_percentage=300;
				} else if($GrossPayTotal >= $philhealthArray[1]['f_range'] && $GrossPayTotal <= $philhealthArray[1]['t_range']) {
					$philhealth_percentage=($GrossPayTotal * 0.03);

				} else {
					$philhealth_percentage=1800;
				}

				$SSSTotal = $SSSTotal/$cutoffTaxDivider;
				$HDMFTotal = $GrossPayTotal*$hdmf_rate;
				$HDMFTotal = $HDMFTotal/$cutoffTaxDivider;
				$HDMFTotalText = $HDMFTotal * 100;
				$PhilHealthTotal = $philhealth_percentage/$cutoffTaxDivider;

				$tStarts = new DateTime($row['DateStarted']);
				$tEnds = new DateTime($row['DateEnds']);

				// Calculating monthly salary to annual salary
				$tDiff = $tEnds->diff($tStarts);
				if ($tDiff->m > 1) {
					$tTotalMonths = $tDiff->y * 12 + $tDiff->m + $tDiff->d / 30;
				} else {
					$tTotalMonths = $tDiff->d;
				}
				if ($row['SalaryExpected'] != NULL && $tTotalMonths > 0) {
					$salaryMonthly = $row['SalaryExpected'] / $tTotalMonths;
				} else {
					$salaryMonthly = 0;
				}
				$annualSalary = $salaryMonthly * 12;
				$year=date("Y");
				if($year<=2022)
				{
					if($annualSalary<=250000) //Not over P250,000 -- 0%
					{
						$tax=0; 
					}
					else if($annualSalary>=250000.01 && $annualSalary <= 400000) 	//Over P250,000 but not over P400,000 -- 20% of the excess over P250,000
					{
						$tax=((($annualSalary-250000)*0.2)/12)/$cutoffTaxDivider;
					} 
					else if($annualSalary>=400000.01 && $annualSalary <= 800000) 	//Over P400,000 but not over P800,000 -- P30,000 + 25% of the excess over P400,000
					{
						$tax=((30000+(($annualSalary-400000)*0.25))/12)/$cutoffTaxDivider; 		 	//divided into 12 for monthly, then divided by 4 for weekly, 2 for semi monthly, 1 for monthly
					}
					else if($annualSalary>=800000.01 && $annualSalary <= 2000000) 	//Over P800,000 but not over P2,000,000 -- P130,000 + 30% of the excess over P800,000
					{
						$tax=((130000+(($annualSalary-800000)*0.3))/12)/$cutoffTaxDivider; 		  	//divided into 12 for monthly, then divided by 4 for weekly, 2 for semi monthly, 1 for monthly
					}
					else if($annualSalary>=2000000.01 && $annualSalary <= 8000000) 	//Over P2,000,000 but not over P8,000,000 -- P490,000 + 32% of the excess over P2,000,000
					{
						$tax=((490000+(($annualSalary-2000000)*0.32))/12)/$cutoffTaxDivider; 		//divided into 12 for monthly, then divided by 4 for weekly, 2 for semi monthly, 1 for monthly
					}
					else 															//Over P8,000,000 -- P2,410,000 + 35% of the excess over P8,000,000
					{
						$tax=((2410000+(($annualSalary-8000000)*0.35))/12)/$cutoffTaxDivider; 		//divided into 12 for monthly, then divided by 4 for weekly, 2 for semi monthly, 1 for monthly
					}

				}
				else
				{
					if($annualSalary<=250000) //Not over P250,000 -- 0%
					{
						$tax=0; 
					}
					else if($annualSalary>=250000.01 && $annualSalary <= 400000) 	//Over P250,000 but not over P400,000 -- 15% of the excess over P250,000
					{
						$tax=((($annualSalary-250000)*0.15)/12)/$cutoffTaxDivider;
					} 
					else if($annualSalary>=400000.01 && $annualSalary <= 800000) 	//Over P400,000 but not over P800,000 -- P22,500 + 20% of the excess over P400,000
					{
						$tax=((22500+(($annualSalary-400000)*0.20))/12)/$cutoffTaxDivider; 		 	//divided into 12 for monthly, then divided by 4 for weekly, 2 for semi monthly, 1 for monthly
					}
					else if($annualSalary>=800000.01 && $annualSalary <= 2000000) 	//Over P800,000 but not over P2,000,000 -- P102,500 + 25% of the excess over P800,000
					{
						$tax=((102500+(($annualSalary-800000)*0.25))/12)/$cutoffTaxDivider; 		  	//divided into 12 for monthly, then divided by 4 for weekly, 2 for semi monthly, 1 for monthly
					}
					else if($annualSalary>=2000000.01 && $annualSalary <= 8000000) 	//Over P2,000,000 but not over P8,000,000 -- P402,500 + 30% of the excess over P2,000,000
					{
						$tax=((402500+(($annualSalary-2000000)*0.30))/12)/$cutoffTaxDivider; 		//divided into 12 for monthly, tthen divided by 4 for weekly, 2 for semi monthly, 1 for monthly
					}
					else 															//Over P8,000,000 -- P2,202,500 + 35% of the excess over P8,000,000
					{
						$tax=((202500+(($annualSalary-8000000)*0.35))/12)/$cutoffTaxDivider; 		//divided into 12 for monthly, then divided by 4 for weekly, 2 for semi monthly, 1 for monthly
					}
				}
				// ~ additional deductions
				$LoansTotal = 0;
				$GetPayrollLoansBetweenPeriod = $this->Model_Selects->GetPayrollLoansBetweenPeriod($ApplicantID, $Mode, $fromDate, $toDate);
				if ($GetPayrollLoansBetweenPeriod->num_rows() > 0) {
					foreach($GetPayrollLoansBetweenPeriod->result_array() as $lrow) {
						$LoansTotal = $LoansTotal + $lrow['Amount'];
					}
				}
				$DeductionsTotal = $SSSTotal + $HDMFTotal + $PhilHealthTotal + $tax + $LoansTotal;
				if ($DeductionsTotal < 0) {
					$DeductionsTotal = 0;
				}
				$NetPay = $GrossPayTotal - $DeductionsTotal;
				if ($NetPay < 0) {
					$NetPay = 0;
				}

				// ~ provisions
				$ProvisionsTotal = 0;
				$GetPayrollProvisionsBetweenPeriod = $this->Model_Selects->GetPayrollProvisionsBetweenPeriod($ApplicantID, $Mode, $fromDate, $toDate);
				if ($GetPayrollProvisionsBetweenPeriod->num_rows() > 0) {
					foreach($GetPayrollProvisionsBetweenPeriod->result_array() as $brow) {
						$ProvisionsTotal = $ProvisionsTotal + $brow['Amount'];
					}
				}
				$ProvisionsNetPay = $NetPay - $ProvisionsTotal;
				// Round off to two decimals
				$SSSTotal = number_format($SSSTotal, 2, '.', '');
				$HDMFTotal = number_format($HDMFTotal, 2, '.', '');
				$PhilHealthTotal = number_format($PhilHealthTotal, 2, '.', '');
				$LoansTotal = number_format($LoansTotal, 2, '.', '');
				$GrossPayTotal = '₱' . number_format($GrossPayTotal, 2, '.', '');
				$DeductionsTotal = '₱' . number_format($DeductionsTotal, 2, '.', '');
				$NetPay = '₱' . number_format($NetPay, 2, '.', '');
				$ProvisionsTotal = '₱' . number_format($ProvisionsTotal, 2, '.', '');
				$ProvisionsNetPay = '₱' . number_format($ProvisionsNetPay, 2, '.', '');
			}
		} else {
			redirect('FourOhFour');
		}
	}
?>
<style>
	html,body
	{
		
	}
	.payslip-container input {
		border: none;
		border-color: transparent;
		width: 100%;
		height: 100%;
		margin: 0;
		padding: 0;
	}
	input[type]:focus {
		box-shadow: none !important;
		background-color: rgba(0, 0, 0, 0.08);
	}
	/*.payslip-container .payslip-text {
		margin-top: -2px;
	}*/
	.payslip-container .payslip-number {
		font-family: Arial;
		text-align: right;
		font-size: 11px;
		padding-left: 5px;
	}
	.payslip-field {
		padding-right: 8px;
	}
</style>
<body>
	<div class="wrapper">
		<?php $this->load->view('_template/users/u_sidebar'); ?>
		<div id="content" class="ncontent">
			<div class="container-fluid">
				<?php $this->load->view('_template/users/u_notifications'); ?>
					<div class="my-3" style="margin-left: 10px;">
						<div class="row">
							<div class="col-sm-12">
								<button type="button" class="btn btn-success glow-gold" onClick="printContent('PrintOut')" style="width: 400px;"><i class="fas fa-print"></i> Print</button>
								<button type="button" class="new-earnings-row-btn btn btn-info"><i class="fas fa-plus"></i> Earnings</button>
								<button type="button" class="new-deductions-row-btn btn btn-info"><i class="fas fa-plus"></i> Deductions</button>
								<button type="button" class="new-provisions-row-btn btn btn-info"><i class="fas fa-plus"></i> Provisions</button>
								<button type="button" class="payroll-changedates-btn btn btn-primary" style="width: 155px;"><i class="fas fa-edit"></i> Change Set</button>
							</div>
						</div>
						<div class="row payroll-changedates-group mt-4" style="margin-left: 10px; border-top: 2px solid rgba(0, 0, 0, 0.16); display: none;">
							<?php echo form_open(base_url().'GeneratePayslip','method="GET" name="PayrollFilterForm"');?>
								<div class="form-row">
									<!-- Applicant -->
									<div class="form-group col-sm-6 col-md-6">
										<label><b>Applicant ID</b></label>
										<input class="form-control" type="text" name="id" value="<?php echo $ApplicantID; ?>">
									</div>
									<!-- Mode -->
									<div class="form-group col-sm-6 col-md-5">
										<label><b>Mode</b></label>
										<select id="ModeSelect" class="payroll-select form-control" name="mode">
											<option value="Weekly" <?php if ($Mode == 0) { echo 'selected'; } ?>>Weekly</option>
											<option value="Semi-monthly" <?php if ($Mode == 1) { echo 'selected'; } ?>>Semi-monthly</option>
											<option value="Monthly" <?php if ($Mode == 2) { echo 'selected'; } ?>>Monthly</option>
										</select>
									</div>
									<!-- From -->
									<div class="form-group col-sm-4 col-md-2">
										<label><b>From</b></label>
										<select id="FromMonthSelect" class="payroll-select form-control" name="from_month">
											<option value="1" <?php if ($fromMonth == 1) { echo 'selected'; } ?>>January</option>
											<option value="2" <?php if ($fromMonth == 2) { echo 'selected'; } ?>>February</option>
											<option value="3" <?php if ($fromMonth == 3) { echo 'selected'; } ?>>March</option>
											<option value="4" <?php if ($fromMonth == 4) { echo 'selected'; } ?>>April</option>
											<option value="5" <?php if ($fromMonth == 5) { echo 'selected'; } ?>>May</option>
											<option value="6" <?php if ($fromMonth == 6) { echo 'selected'; } ?>>June</option>
											<option value="7" <?php if ($fromMonth == 7) { echo 'selected'; } ?>>July</option>
											<option value="8" <?php if ($fromMonth == 8) { echo 'selected'; } ?>>August</option>
											<option value="9" <?php if ($fromMonth == 9) { echo 'selected'; } ?>>September</option>
											<option value="10" <?php if ($fromMonth == 10) { echo 'selected'; } ?>>October</option>
											<option value="11" <?php if ($fromMonth == 11) { echo 'selected'; } ?>>November</option>
											<option value="12" <?php if ($fromMonth == 12) { echo 'selected'; } ?>>December</option>
										</select>
									</div>
									<div class="form-group col-sm-4 col-md-1">
										<label>&nbsp;</label>
										<input class="form-control" type="number" name="from_day" value="<?php echo $fromDay; ?>">
									</div>
									<div class="form-group col-sm-4 col-md-2">
										<label>&nbsp;</label>
										<input class="form-control" type="number" name="from_year" value="<?php echo $fromYear; ?>">
									</div>
									<!-- To -->
									<div class="form-group col-sm-4 col-md-2">
										<label><b>To</b></label>
										<select id="FromMonthSelect" class="payroll-select form-control" name="to_month">
											<option value="1" <?php if ($toMonth == 1) { echo 'selected'; } ?>>January</option>
											<option value="2" <?php if ($toMonth == 2) { echo 'selected'; } ?>>February</option>
											<option value="3" <?php if ($toMonth == 3) { echo 'selected'; } ?>>March</option>
											<option value="4" <?php if ($toMonth == 4) { echo 'selected'; } ?>>April</option>
											<option value="5" <?php if ($toMonth == 5) { echo 'selected'; } ?>>May</option>
											<option value="6" <?php if ($toMonth == 6) { echo 'selected'; } ?>>June</option>
											<option value="7" <?php if ($toMonth == 7) { echo 'selected'; } ?>>July</option>
											<option value="8" <?php if ($toMonth == 8) { echo 'selected'; } ?>>August</option>
											<option value="9" <?php if ($toMonth == 9) { echo 'selected'; } ?>>September</option>
											<option value="10" <?php if ($toMonth == 10) { echo 'selected'; } ?>>October</option>
											<option value="11" <?php if ($toMonth == 11) { echo 'selected'; } ?>>November</option>
											<option value="12" <?php if ($toMonth == 12) { echo 'selected'; } ?>>December</option>
										</select>
									</div>
									<div class="form-group col-sm-4 col-md-1">
										<label>&nbsp;</label>
										<input class="form-control" type="number" name="to_day" value="<?php echo $toDay; ?>">
									</div>
									<div class="form-group col-sm-4 col-md-2">
										<label>&nbsp;</label>
										<input class="form-control" type="number" name="to_year" value="<?php echo $toYear; ?>">
									</div>
									<!-- Filter -->
									<div class="form-group col-sm-6 col-md-1">
										<label>&nbsp;</label>
										<button type="submit" class="btn btn-primary w-100"><i class="fas fa-search"></i> Find</button>
									</div>
								</div>
							<?php echo form_close();?>
						</div>
					</div>
					<div class="row PrintOut" style="margin-left: 20px;">
						<?php for ($i = 0; $i < 2; $i++): ?>
							<div class="payslip-container col-sm-5" style="margin-left: 5px;">
								<div class="row mt-2" style="border-bottom: 1px solid black;">
									<div class="col-sm-1">
										<img src="<?=base_url()?>assets/img/wercher_logo.png" width="78" height="69" style="filter: grayscale(100%); margin-top: -5px;">
									</div>
									<div class="col-sm-11 text-center">
										<span style="font-size: 16px;"><b>WERCHER SOLUTIONS AND RESOURCES</b></span>
										<p><span style="font-size: 16px;"><b>LABOR SERVICE COOPERATIVE</b></span></p>
									</div>
								</div>
								<div class="row mt-2" style="border-bottom: 1px solid black;">
									<div class="col-sm-12">
										<div class="row">
											<div class="payslip-field col-sm-2">
												<input type="text" value="PERIOD:">
											</div>
											<div class="payslip-text col-sm-10">
												<input style="font-weight: bold;" type="text" value="<?php echo $payrollPeriod ; ?> (<?php echo $ModeRaw; ?>)">
											</div>
										</div>
									</div>
									<div class="col-sm-12">
										<div class="row">
											<div class="payslip-field col-sm-2">
												<input type="text" value="NAME:">
											</div>
											<div class="payslip-field-value col-sm-10">
												<input style="font-weight: bold; font-size: 18px;" type="text" value="<?php echo $fullName ; ?>">
											</div>
										</div>
									</div>
									<div class="col-sm-12">
										<div class="row">
											<div class="payslip-field col-sm-2">
												<input type="text" value="RATE:">
											</div>
											<div class="payslip-field-value col-sm-10">
												<input style="font-weight: bold;" type="text" value="PHP <?php echo $rate ; ?> (<?php echo $salaryType; ?>)">
											</div>
										</div>
									</div>
									<div class="col-sm-12">
										<div class="row">
											<div class="payslip-field col-sm-2">
												<input type="text" value="CLIENT:">
											</div>
											<div class="payslip-field-value col-sm-10">
												<input style="font-weight: bold;" type="text" value="<?php echo $ClientName ; ?>">
											</div>
										</div>
									</div>
									<div class="col-sm-12">
										<div class="row">
											<div style="margin-bottom: 2px;" class="col-sm-12">
												<input class="payslip-header" type="text" value="GROSS PAY Breakdown (Hours / Rate):">
											</div>
										</div>
									</div>
								</div>
								<div class="row" style="border-bottom: 1px solid black;">
									<div class="col-sm-6" style="border-right: 1px solid black;">
										<div class="row">
											<div class="col-sm-12">
												<input style="font-weight: bold;" type="text" value="Earnings">
											</div>
										</div>
										<div class="row">
											<div class="payslip-field col-sm-3">
												<input type="text" value="REG:">
											</div>
											<div class="col-sm-4 text-right">
												<input class="payslip-number" type="text" value="<?php echo $RegularHours; ?>" name="RegularHours">
											</div>
											<div class="col-sm-5 text-right">
												<input class="payslip-number" type="text" value="<?php echo $RegularGrossPay; ?>" name="RegularGrossPay">
											</div>
											<div class="payslip-field col-sm-3">
												<input type="text" value="OT:">
											</div>
											<div class="col-sm-4 text-right">
												<input class="payslip-number" type="text" value="<?php echo $RegularOvertime; ?>" name="RegularOvertime">
											</div>
											<div class="col-sm-5 text-right">
												<input class="payslip-number" type="text" value="<?php echo $RegularOvertimeGrossPay; ?>" name="RegularOvertimeGrossPay">
											</div>
											<div class="payslip-field col-sm-3">
												<input type="text" value="RD:">
											</div>
											<div class="col-sm-4 text-right">
												<input class="payslip-number" type="text" value="<?php echo $RestDayHours; ?>" name="RestDayHours">
											</div>
											<div class="col-sm-5 text-right">
												<input class="payslip-number" type="text" value="<?php echo $RestDayGrossPay; ?>" name="RegularGrossPay">
											</div>
											<div class="payslip-field col-sm-3">
												<input type="text" value="OT:">
											</div>
											<div class="col-sm-4 text-right">
												<input class="payslip-number" type="text" value="<?php echo $RestDayOvertime; ?>">
											</div>
											<div class="col-sm-5 text-right">
												<input class="payslip-number" type="text" value="<?php echo $RestDayOvertimeGrossPay; ?>">
											</div>
											<div class="payslip-field col-sm-3">
												<input type="text" value="SH:">
											</div>
											<div class="col-sm-4 text-right">
												<input class="payslip-number" type="text" value="<?php echo $SpecialHolidayHours; ?>">
											</div>
											<div class="col-sm-5 text-right">
												<input class="payslip-number" type="text" value="<?php echo $SpecialHolidayGrossPay; ?>">
											</div>
											<div class="payslip-field col-sm-3">
												<input type="text" value="OT:">
											</div>
											<div class="col-sm-4 text-right">
												<input class="payslip-number" type="text" value="<?php echo $SpecialHolidayOvertime; ?>">
											</div>
											<div class="col-sm-5 text-right">
												<input class="payslip-number" type="text" value="<?php echo $SpecialHolidayOvertimeGrossPay; ?>">
											</div>
											<div class="payslip-field col-sm-3">
												<input type="text" value="SHRD:">
											</div>
											<div class="col-sm-4 text-right">
												<input class="payslip-number" type="text" value="<?php echo $SPHRDHours; ?>">
											</div>
											<div class="col-sm-5 text-right">
												<input class="payslip-number" type="text" value="<?php echo $SPHRDGrossPay; ?>">
											</div>
											<div class="payslip-field col-sm-3">
												<input type="text" value="OT:">
											</div>
											<div class="col-sm-4 text-right">
												<input class="payslip-number" type="text" value="<?php echo $SPHRDOvertime; ?>">
											</div>
											<div class="col-sm-5 text-right">
												<input class="payslip-number" type="text" value="<?php echo $SPHRDOvertimeGrossPay; ?>">
											</div>
											<div class="payslip-field col-sm-3">
												<input type="text" value="NH100:">
											</div>
											<div class="col-sm-4 text-right">
												<input class="payslip-number" type="text" value="<?php echo $NHOHPHours; ?>">
											</div>
											<div class="col-sm-5 text-right">
												<input class="payslip-number" type="text" value="<?php echo $NHOHPGrossPay; ?>">
											</div>
											<div class="payslip-field col-sm-3">
												<input type="text" value="NH200:">
											</div>
											<div class="col-sm-4 text-right">
												<input class="payslip-number" type="text" value="<?php echo $NationalHolidayHours; ?>">
											</div>
											<div class="col-sm-5 text-right">
												<input class="payslip-number" type="text" value="<?php echo $NationalHolidayGrossPay; ?>">
											</div>
											<div class="payslip-field col-sm-3">
												<input type="text" value="OT:">
											</div>
											<div class="col-sm-4 text-right">
												<input class="payslip-number" type="text" value="<?php echo $NationalHolidayOvertime; ?>">
											</div>
											<div class="col-sm-5 text-right">
												<input class="payslip-number" type="text" value="<?php echo $NationalHolidayOvertimeGrossPay; ?>">
											</div>
											<div class="payslip-field col-sm-3">
												<input type="text" value="NHRD:">
											</div>
											<div class="col-sm-4 text-right">
												<input class="payslip-number" type="text" value="<?php echo $NHRDHours; ?>">
											</div>
											<div class="col-sm-5 text-right">
												<input class="payslip-number" type="text" value="<?php echo $NHRDGrossPay; ?>">
											</div>
											<div class="payslip-field col-sm-3" style="margin-bottom: 2px;">
												<input type="text" value="OT:">
											</div>
											<div class="col-sm-4 text-right" style="margin-bottom: 2px;">
												<input class="payslip-number" type="text" value="<?php echo $NHRDOvertime; ?>">
											</div>
											<div class="col-sm-5 text-right" style="margin-bottom: 2px;">
												<input class="payslip-number" type="text" value="<?php echo $NHRDOvertimeGrossPay; ?>">
											</div>
										</div>
									</div>
									<div class="col-sm-6">
										<div class="row">
											<div class="col-sm-12">
												<input style="font-weight: bold;" type="text" value="Night Premium">
											</div>
										</div>
										<div class="row">
											<div class="payslip-field col-sm-3">
												<input type="text" value="NP:">
											</div>
											<div class="col-sm-4 text-right">
												<input class="payslip-number" type="text" value="<?php echo $RegularNightHours; ?>">
											</div>
											<div class="col-sm-5 text-right">
												<input class="payslip-number" type="text" value="<?php echo $RegularNPGrossPay; ?>">
											</div>
											<div class="payslip-field col-sm-3">
												<input type="text" value="OTNP:">
											</div>
											<div class="col-sm-4 text-right">
												<input class="payslip-number" type="text" value="<?php echo $RegularNightOvertime; ?>">
											</div>
											<div class="col-sm-5 text-right">
												<input class="payslip-number" type="text" value="<?php echo $RegularNPOvertimeGrossPay; ?>">
											</div>
											<div class="payslip-field col-sm-3">
												<input type="text" value="NP:">
											</div>
											<div class="col-sm-4 text-right">
												<input class="payslip-number" type="text" value="<?php echo $RestDayNightHours; ?>">
											</div>
											<div class="col-sm-5 text-right">
												<input class="payslip-number" type="text" value="<?php echo $RestDayNPGrossPay; ?>">
											</div>
											<div class="payslip-field col-sm-3">
												<input type="text" value="OTNP:">
											</div>
											<div class="col-sm-4 text-right">
												<input class="payslip-number" type="text" value="<?php echo $RestDayNightOvertime; ?>">
											</div>
											<div class="col-sm-5 text-right">
												<input class="payslip-number" type="text" value="<?php echo $RestDayNPOvertimeGrossPay; ?>">
											</div>
											<div class="payslip-field col-sm-3">
												<input type="text" value="NP:">
											</div>
											<div class="col-sm-4 text-right">
												<input class="payslip-number" type="text" value="<?php echo $SpecialHolidayNightHours; ?>">
											</div>
											<div class="col-sm-5 text-right">
												<input class="payslip-number" type="text" value="<?php echo $SpecialHolidayNPGrossPay; ?>">
											</div>
											<div class="payslip-field col-sm-3">
												<input type="text" value="OTNP:">
											</div>
											<div class="col-sm-4 text-right">
												<input class="payslip-number" type="text" value="<?php echo $SpecialHolidayNightOvertime; ?>">
											</div>
											<div class="col-sm-5 text-right">
												<input class="payslip-number" type="text" value="<?php echo $SpecialHolidayNPOvertimeGrossPay; ?>">
											</div>
											<div class="payslip-field col-sm-3">
												<input type="text" value="NP:">
											</div>
											<div class="col-sm-4 text-right">
												<input class="payslip-number" type="text" value="<?php echo $SPHRDNightHours; ?>">
											</div>
											<div class="col-sm-5 text-right">
												<input class="payslip-number" type="text" value="<?php echo $SPHRDNPGrossPay; ?>">
											</div>
											<div class="payslip-field col-sm-3">
												<input type="text" value="OTNP:">
											</div>
											<div class="col-sm-4 text-right">
												<input class="payslip-number" type="text" value="<?php echo $SPHRDNightOvertime; ?>">
											</div>
											<div class="col-sm-5 text-right">
												<input class="payslip-number" type="text" value="<?php echo $SPHRDNPOvertimeGrossPay; ?>">
											</div>
											<div class="payslip-field col-sm-3">
												<input type="text" value="NP:">
											</div>
											<div class="col-sm-4 text-right">
												<input class="payslip-number" type="text" value="<?php echo $NationalHolidayNightHours; ?>">
											</div>
											<div class="col-sm-5 text-right">
												<input class="payslip-number" type="text" value="<?php echo $NationalHolidayNPGrossPay; ?>">
											</div>
											<div class="payslip-field col-sm-3">
												<input type="text" value="OTNP:">
											</div>
											<div class="col-sm-4 text-right">
												<input class="payslip-number" type="text" value="<?php echo $NationalHolidayNightOvertime; ?>">
											</div>
											<div class="col-sm-5 text-right">
												<input class="payslip-number" type="text" value="<?php echo $NationalHolidayNPOvertimeGrossPay; ?>">
											</div>
											<div class="payslip-field col-sm-3">
												<input type="text" value="NP:">
											</div>
											<div class="col-sm-4 text-right">
												<input class="payslip-number" type="text" value="<?php echo $NHRDNightHours; ?>">
											</div>
											<div class="col-sm-5 text-right">
												<input class="payslip-number" type="text" value="<?php echo $NHRDNPGrossPay; ?>">
											</div>
											<div class="payslip-field col-sm-3" style="margin-bottom: 2px;">
												<input type="text" value="OTNP:">
											</div>
											<div class="col-sm-4 text-right" style="margin-bottom: 2px;">
												<input class="payslip-number" type="text" value="<?php echo $NHRDNightOvertime; ?>">
											</div>
											<div class="col-sm-5 text-right" style="margin-bottom: 2px;">
												<input class="payslip-number" type="text" value="<?php echo $NHRDNPOvertimeGrossPay; ?>">
											</div>
										</div>
									</div>
								</div>
								<div class="row" style="margin-top: 2px;">
									<div class="col-sm-6">
										<div class="row">
											<div class="col-sm-6">
												<input type="text" value="TOTAL:">
											</div>
											<div class="col-sm-6 text-right">
												<input class="payslip-number" type="text" value="<?php echo $EarningsTotal; ?>">
											</div>
										</div>
									</div>
									<div class="col-sm-6">
										<div class="row">
											<div class="col-sm-6">
												<input type="text" value="TOTAL:">
											</div>
											<div class="col-sm-6 text-right">
												<input class="payslip-number" type="text" value="<?php echo $NightPremiumTotal; ?>">
											</div>
										</div>
									</div>
								</div>
								<div class="row">
									<div class="col-sm-12">
										<div class="row">
											<div class="col-sm-6">
												<input type="text" value="ALLOWANCE:">
											</div>
											<div class="col-sm-6 text-right">
												<input class="payslip-number" type="text" value="<?php echo $EarningsTotal; ?>">
											</div>
										</div>
									</div>
								</div>
								<div class="row">
									<div class="col-sm-12">
										<div class="row">
											<div class="col-sm-6">
												<input type="text" value="VL / SL:">
											</div>
											<div class="col-sm-6 text-right">
												<input class="payslip-number" type="text" value="<?php echo $SSSAmountPaidTotal; ?>">
											</div>
										</div>
									</div>
								</div>
								<div class="NewEarningsContainer row" style="margin-bottom: 2px;">
								</div>
								<div class="row" style="border-top: 1px solid black; border-bottom: 1px solid black;">
									<div class="col-sm-12">
										<div class="row" style="font-size: 17px;">
											<div class="col-sm-6" style="margin-top: 1px; margin-bottom: 1px;">
												<input style="font-weight: bold;" type="text" value="TOTAL GROSS PAY:">
											</div>
											<div class="col-sm-6 text-right" style="margin-top: 1px; margin-bottom: 1px;">
												<input style="font-size: 17px; font-weight: bold;" class="payslip-number" type="text" value="<?php echo $GrossPayTotal; ?>">
											</div>
										</div>
									</div>
								</div>
								<div class="row">
									<div class="col-sm-12">
										<div class="row" style="margin-top: 2px;">
											<div class="col-sm-12">
												<input style="font-weight: bold;" type="text" value="Deductions">
											</div>
										</div>
										<div class="row">
											<div class="payslip-field col-sm-3">
												<input type="text" value="PhilHealth:">
											</div>
											<div class="col-sm-3 text-right">
												<input class="payslip-number" type="text" value="<?php echo $PhilHealthTotal; ?>">
											</div>
											<div class="payslip-field col-sm-3">
												<input type="text" value="Pag-ibig:">
											</div>
											<div class="col-sm-3 text-right">
												<input class="payslip-number" type="text" value="<?php echo $HDMFTotal; ?>">
											</div>
											<div class="payslip-field col-sm-3">
												<input type="text" value="Tax W/held:">
											</div>
											<div class="col-sm-3 text-right">
												<input class="payslip-number" type="text" value="<?php echo $tax; ?>">
											</div>
											<div class="payslip-field col-sm-3">
												<input type="text" value="SSS:">
											</div>
											<div class="col-sm-3 text-right">
												<input class="payslip-number" type="text" value="<?php echo $SSSTotal; ?>">
											</div>
										</div>
										<div class="NewDeductionsContainer row">
										</div>
									</div>
									<?php if ($GetPayrollLoansBetweenPeriod->num_rows() > 0): ?>
									<div class="col-sm-12">
										<div class="row">
											<div class="col-sm-12">
												<input style="font-weight: bold;" type="text" value="Additional">
											</div>
										</div>
										<div class="row">
											<?php foreach($GetPayrollLoansBetweenPeriod->result_array() as $prow): ?>
											<div class="payslip-field col-sm-3">
												<input type="text" value="<?php echo $prow['LoanName'] ?>:">
											</div>
											<div class="col-sm-3 text-right">
												<input class="payslip-number" type="text" value="<?php echo $prow['Amount']; ?>">
											</div>
											<?php endforeach; ?>
										</div>
									</div>
									<?php endif; ?>
								</div>
								<div class="row" style="border-top: 1px solid black; border-bottom: 1px solid black; margin-top: 2px;">
									<div class="col-sm-12">
										<div class="row" style="font-size: 17px;">
											<div class="col-sm-6" style="margin-top: 1px; margin-bottom: 1px;">
												<input style="font-weight: bold;" type="text" value="TOTAL DEDUCTIONS:">
											</div>
											<div class="col-sm-6 text-right" style="margin-top: 1px; margin-bottom: 1px;">
												<input style="font-size: 17px; font-weight: bold;" class="payslip-number" type="text" value="<?php echo $DeductionsTotal; ?>">
											</div>
										</div>
									</div>
								</div>
								<div class="row" style="border-bottom: 1px solid black;">
									<div class="col-sm-12">
										<div class="row" style="font-size: 19px;">
											<div class="col-sm-4" style="margin-top: 1px; margin-bottom: 1px;">
												<input style="font-weight: bold; font-size: 19px" type="text" value="NET PAY:">
											</div>
											<div class="col-sm-8 text-right" style="margin-top: 1px; margin-bottom: 1px;">
												<input style="font-family: Courier; font-size: 19px; font-weight: bold;" class="payslip-number" type="text" value="<?php echo $NetPay; ?>">
											</div>
										</div>
									</div>
								</div>
								<div class="row">
									<div class="col-sm-4" style="margin-top: 1px; margin-bottom: 1px;">
										<input type="text" value="Received by:">
									</div>
									<div class="col-sm-8" style="margin-bottom: 6px;">
										<div class="row w-75" style="margin-top: 50px;">
											<div class="col-sm-12" style="border-top: 1px solid black;">
												<input class="text-center" style="font-size: 10px; font-weight: bold;" type="text" value="Signature Over Printed Name">
											</div>
											<div class="col-sm-12" style="border-top: 1px solid black; margin-top: 20px;">
												<input class="text-center" style="font-size: 10px; font-weight: bold;" type="text" value="Date Received">
											</div>
										</div>
									</div>
								</div>
								<div class="row" style="border-top: 1px solid black; border-bottom: 1px solid black;">
									<div class="col-sm-12" style="margin-top: 2px; margin-bottom: 2px;">
										<input class="text-center" style="font-size: 12px;" type="text" value="----------------------------------------------------------------------">
									</div>
								</div>
								<div class="row" style="margin-top: 2px; border-bottom: 1px solid black;">
									<div class="col-sm-12" style="margin-bottom: 2px;">
										<input style="font-size: 17px; font-weight: bold; margin-bottom: 2px;" class="text-center" type="text" value="PROVISIONAL RECEIPT">
									</div>
								</div>
								<div class="row" style="font-size: 12px;">
									<div class="col-sm-12" style="margin-top: 8px;">
										<input style="font-weight: bold;" type="text" value="Received from <?php echo $fullName; ?>">
									</div>
									<div class="col-sm-12">
										<input style="font-weight: bold;" type="text" value="the amount of <?php echo $ProvisionsTotal ?> as payment for the following:">
									</div>
								</div>
								<?php if ($GetPayrollProvisionsBetweenPeriod->num_rows() > 0): ?>
								<div class="row">
									<?php foreach($GetPayrollProvisionsBetweenPeriod->result_array() as $vrow): ?>
									<div class="payslip-field col-sm-3">
										<input type="text" value="<?php echo $vrow['ProvisionName'] ?>:">
									</div>
									<div class="col-sm-3 text-right">
										<input class="payslip-number" type="text" value="<?php echo $vrow['Amount']; ?>">
									</div>
									<?php endforeach; ?>
								</div>
								<?php endif; ?>
								<div class="NewProvisionsContainer row">
								</div>
								<div class="row" style="border-top: 1px solid black; border-bottom: 1px solid black; margin-top: 2px;">
									<div class="col-sm-12">
										<div class="row" style="font-size: 17px;">
											<div class="col-sm-6" style="margin-top: 1px; margin-bottom: 1px;">
												<input style="font-weight: bold;" type="text" value="TOTAL PAYMENTS:">
											</div>
											<div class="col-sm-6 text-right" style="margin-top: 1px; margin-bottom: 1px;">
												<input style="font-size: 17px; font-weight: bold;" class="payslip-number" type="text" value="<?php echo $ProvisionsTotal; ?>">
											</div>
										</div>
									</div>
								</div>
								<div class="row">
									<div class="col-sm-12">
										<div class="row" style="font-size: 19px;">
											<div class="col-sm-4" style="margin-top: 1px; margin-bottom: 1px;">
												<input style="font-weight: bold; font-size: 19px" type="text" value="NET PAY:">
											</div>
											<div class="col-sm-8 text-right" style="margin-top: 1px; margin-bottom: 1px;">
												<input style="font-family: Courier; font-size: 19px; font-weight: bold;" class="payslip-number" type="text" value="<?php echo $ProvisionsNetPay; ?>">
											</div>
										</div>
									</div>
								</div>
							</div>
						<?php endfor; ?>
					</div>
				</div>
			</div>
		</div>
	</div>
</body>
<?php $this->load->view('_template/users/u_scripts');?>
<script type="text/javascript" src="<?php echo base_url(); ?>assets/js/jquery.textfill.min.js"></script>
<script type="text/javascript">
	$(document).ready(function () {
		$(".nav-item a[href*='Payroll']").addClass("nactive");
		$('[data-toggle="tooltip"]').tooltip();
		$('.new-earnings-row-btn').on('click', function () {
			$('.NewEarningsContainer').append('<div class="col-sm-12"><div class="row"><div class="col-sm-6"><input type="text" value="#####:"></div><div class="col-sm-6 text-right"><input class="payslip-number" type="text" value="####"></div></div></div>');
		});
		$('.new-deductions-row-btn').on('click', function () {
			$('.NewDeductionsContainer').append('<div class="payslip-field col-sm-3"><input type="text" value="#####:"></div><div class="col-sm-3 text-right"><input class="payslip-number" type="text" value="####"></div>');
		});
		$('.new-provisions-row-btn').on('click', function () {
			$('.NewProvisionsContainer').append('<div class="payslip-field col-sm-3"><input type="text" value="#####:"></div><div class="col-sm-3 text-right"><input class="payslip-number" type="text" value="####"></div>');
		});
		let changePayroll = false;
		$('.payroll-changedates-btn').on('click', function() {
			if (!changePayroll) {
				changePayroll = true;
				$(this).html('<i class="fas fa-times"></i> Change Set');
				$('.payroll-changedates-group').show();
			} else {
				changePayroll = false;
				$(this).html('<i class="fas fa-edit"></i> Change Set');
				$('.payroll-changedates-group').hide();
			}
		});
	});
</script>