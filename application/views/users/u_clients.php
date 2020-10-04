<?php 

$T_Header;
require 'vendor/autoload.php';
use Carbon\Carbon;

?>
<body>
	<div class="wrapper wercher-background-lowpoly">
		<?php $this->load->view('_template/users/u_sidebar'); ?>
		<div id="content" class="ncontent">
			<div class="container-fluid">
				<?php $this->load->view('_template/users/u_notifications'); ?>
				<div class="col-12 col-sm-12 tabs">
					<ul>
						<li class="tabs-active"><a href="<?php echo base_url() ?>Clients">Clients (<?php echo $ShowClients->num_rows()?>)</a></li>
						<li><a href="<?php echo base_url() ?>ClientsArchived">Archived</a></li>
					</ul>
				</div>
				<div class="row rcontent">
					<div class="col-5 PrintPageName PrintOut">
						<i class="fas fa-info-circle"></i>
						<i>Found <?php echo $ShowClients->num_rows(); ?> client<?php if($ShowClients->num_rows() != 1): echo 's'; endif;?> currently stored in the database.
						</i>
					</div>
					<div class="col-7 text-right">
						<span class="input-bootstrap">
							<i class="sorting-table-icon spinner-border spinner-border-sm mr-2"></i>
							<input id="DTSearch" type="search" class="input-bootstrap" placeholder="Sorting table..." readonly>
						</span>
						<button class="btn btn-success" data-toggle="modal" data-target="#addClients">
							<i class="fas fa-user-plus"></i> New
						</button>
						<button type="button" class="btn btn-primary" data-toggle="modal" data-target="#ExportModal"><i class="fas fa-download"></i> Export</button>
					</div>
					<div class="col-sm-12">
						<div class="table-responsive pt-2 pb-5 pl-2 pr-2">
							<table id="ListClients" class="table PrintOut" style="width: 100%;">
								<thead>
									<tr class="text-center align-middle">
										<th style="width: 100px;"> Name </th>
										<th style="width: 225px;"> Address </th>
										<th> Contact </th>
										<th> ID Suffix </th>
										<th style="width: 25px;"> Employees </th>
										<th> Date Added </th>
										<th class="d-none"> Date Added </th>
										<th class="text-center PrintExclude" style="width: 100px;"> Action </th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ($ShowClients->result_array() as $row): 
										$date = new DateTime($row['DateAdded']);
										$day = $date->format('Y-m-d');
										$day = DateTime::createFromFormat('Y-m-d', $day)->format('F d, Y');
										$hours = $date->format('h:i:s A');
										$elapsed = Carbon::parse($date);

										?>
										<tr class="text-center align-middle">
											<td>
												<?php echo $row['Name']; ?>
											</td>
											<td>
												<?php echo $row['Address']; ?>
											</td>
											<td>
												<?php echo $row['ContactNumber']; ?>
											</td>
											<td>
												<span style="color: rgba(0, 0, 0, 0.33);">WC</span><?php echo $row['EmployeeIDSuffix']; ?><span style="color: rgba(0, 0, 0, 0.5);">-####-<?php 
													$now = new DateTime();
													$currentYear = $now->format('Y');
													echo $currentYear; 
												?>		
												</span>
											</td>
											<td>
												<?php echo $this->Model_Selects->GetWeeklyListEmployee($row['ClientID'])->num_rows(); ?>
											</td>
											<td class="text-center align-middle" data-toggle="tooltip" data-placement="top" data-html="true" title="<?php echo $elapsed->diffForHumans(); ?>">
												<div class="d-none">
													<?php echo $row['DateAdded']; ?>
												</div>
												<?php
													echo $day . '<br>' . $hours;
												?>
											</td>
											<td class="text-center align-middle d-none">
												<?php echo $day . ' at ' . $hours; ?>
											</td>
											<td class="text-center align-middle PrintExclude">
												<a class="btn btn-primary btn-sm w-100 mb-1" href="<?=base_url()?>Clients?id=<?php echo $row['ClientID']; ?>"><i class="fas fa-users"></i> Employees</a>
												<a href="<?=base_url()?>RemoveClient?id=<?=$row['ClientID']?>" class="btn btn-danger btn-sm w-100 mb-1" onclick="return confirm('Remove Client?')"><i class="fas fa-trash"></i> Delete</a>
											</td>
										</tr>
									<?php endforeach ?>
								</tbody>
							</table>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<!-- MODALS -->
	<div class="modal fade" id="addClients" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
		<div class="modal-dialog" role="document">
			<div class="modal-content">
				<?php echo form_open(base_url().'Add_newClient','method="post"');?>
				<div class="modal-header">
					<h5 class="modal-title" id="exampleModalLabel">Add New Client</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<div class="form-row">
						<div class="form-group col-sm-12">
							<label>Name</label>
							<input class="form-control" type="text" name="ClientName" autocomplete="off">
						</div>
					</div>
					<div class="form-row">
						<div class="form-group col-sm-12">
							<label>Address</label>
							<input class="form-control" type="text" name="ClientAddress" autocomplete="off">
						</div>
					</div>
					<div class="form-row">
						<div class="form-group col-sm-12">
							<label>Contact Number</label>
							<input class="form-control" type="text" name="ClientContact" autocomplete="off">
						</div>
					</div>
					<div class="form-row">
						<div class="form-group col-sm-5">
							<label>Employee ID Suffix <span style="color: rgba(0, 0, 0, 0.55);" data-toggle="tooltip" data-placement="top" data-html="true" title="Applicants who get hired to this client will be assigned the designated Employee ID with this as the suffix. See the preview for an example.<br><br>By default, all ID follows the format of WC(Suffix)-NUMBER-YEAR. You can manually change the ID of an applicant whenever they are hired."><i>(?)</i></span></label>
							<input id="EmployeeIDSuffix" class="form-control" type="text" name="EmployeeIDSuffix" autocomplete="off">
						</div>
						<div class="form-group col-sm-2 text-center">
							<p><i class="fas fa-arrow-right" style="margin-right: -1px; color: rgba(0, 0, 0, 0.55);"></i></p>
							<p><i class="fas fa-arrow-right" style="margin-right: -1px; color: rgba(0, 0, 0, 0.55);"></i></p>
						</div>
						<div class="form-group col-sm-5">
							<label>Preview</label>
							<input id="SuffixPreview" class="form-control" type="text" autocomplete="off" readonly>
						</div>
					</div>
				</div>
				<div class="modal-footer">
					<button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Add</button>
				</div>
				<?php echo form_close();?>
			</div>
		</div>
	</div>
	<!-- EXPORT MODAL -->
	<?php $this->load->view('_template/modals/m_export'); ?>
	<!-- CLIENTS EMPLOYED MODAL -->
	<?php $this->load->view('_template/modals/m_clientemployees'); ?>
</body>
<?php $this->load->view('_template/users/u_scripts'); ?>
<script type="text/javascript">
	$(document).ready(function () {
		$('.sorting-table-icon').hide();
		$('#DTSearch').attr('placeholder', 'Search table');
		$('#DTSearch').attr('readonly', false);
		<?php if (isset($_GET['id'])): ?>
			$('#ClientsEmployedModal').modal('show');
		<?php endif; ?>
		$("#ClientsEmployedModal").on("hidden.bs.modal", function () { // Change URL on modal close
		    history.pushState(null, null, '<?php echo base_url() . 'Clients';  ?>');
		});
		$('#EmployeeIDSuffix').bind('input', function() {
			$('#SuffixPreview').val('WC' + $(this).val() + '-####-20');
		});
		$('[data-toggle="tooltip"]').tooltip();
		if (localStorage.getItem('SidebarVisible') == 'true') {
			$('#sidebar').addClass('active');
			$('.ncontent').addClass('shContent');
		} else {
			$('#sidebar').css('transition', 'all 0.3s');
			$('#content').css('transition', 'all 0.3s');
		}
		$('#sidebarCollapse').on('click', function () {
			if (localStorage.getItem('SidebarVisible') == 'false') {
				$('#sidebar').addClass('active');
				$('.ncontent').addClass('shContent');
				$('#sidebar').css('transition', 'all 0.3s');
				$('#content').css('transition', 'all 0.3s');
		    	localStorage.setItem('SidebarVisible', 'true');
			} else {
				$('#sidebar').removeClass('active');
				$('.ncontent').removeClass('shContent');
				$('#sidebar').css('transition', 'all 0.3s');
				$('#content').css('transition', 'all 0.3s');
		    	localStorage.setItem('SidebarVisible', 'false');
			}
		});
		var table = $('#ListClients').DataTable( {
			sDom: 'lrtip',
			"bLengthChange": false,
			"order": [[ 4, "desc" ]],
			buttons: [
            {
	            extend: 'print',
	            exportOptions: {
	                columns: [ 0, 1, 2, 3, 4, 6 ]
	            }
	        },
	        {
	            extend: 'copyHtml5',
	            exportOptions: {
	                columns: [ 0, 1, 2, 3, 4, 6 ]
	            }
	        },
	        {
	            extend: 'excelHtml5',
	            exportOptions: {
	                columns: [ 0, 1, 2, 3, 4, 6 ]
	            }
	        },
	        {
	            extend: 'csvHtml5',
	            exportOptions: {
	                columns: [ 0, 1, 2, 3, 4, 6 ]
	            }
	        },
	        {
	            extend: 'pdfHtml5',
	            exportOptions: {
	                columns: [ 0, 1, 2, 3, 4, 6 ]
	            }
	        }
        ]
   		});
		$('#ExportPrint').on('click', function () {
	        table.button('0').trigger();
	    });
	    $('#ExportCopy').on('click', function () {
	        table.button('1').trigger();
	    });
	    $('#ExportExcel').on('click', function () {
	        table.button('2').trigger();
	    });
	    $('#ExportCSV').on('click', function () {
	        table.button('3').trigger();
	    });
	    $('#ExportPDF').on('click', function () {
	        table.button('4').trigger();
    	});
    	$('#DTSearch').on('keyup change', function(){
			table.search($(this).val()).draw();
		});
    	var ClientTable = $('#ClientEmployedTable').DataTable( {
			"order": [[ 8, "asc" ]],
			buttons: [
            {
	            extend: 'print',
	            exportOptions: {
	                columns: [ 0, 2, 3, 4, 6, 7 ]
	            }
	        },
	        {
	            extend: 'copyHtml5',
	            exportOptions: {
	                columns: [ 0, 2, 3, 4, 6, 7 ]
	            }
	        },
	        {
	            extend: 'excelHtml5',
	            exportOptions: {
	                columns: [ 0, 2, 3, 4, 6, 7 ]
	            }
	        },
	        {
	            extend: 'csvHtml5',
	            exportOptions: {
	                columns: [ 0, 2, 3, 4, 6, 7 ]
	            }
	        },
	        {
	            extend: 'pdfHtml5',
	            exportOptions: {
	                columns: [ 0, 2, 3, 4, 6, 7 ]
	            }
	        }
        ]
   		});
		$('#ClientExportPrint').on('click', function () {
	        ClientTable.button('0').trigger();
	    });
	    $('#ClientExportCopy').on('click', function () {
	        ClientTable.button('1').trigger();
	    });
	    $('#ClientExportExcel').on('click', function () {
	        ClientTable.button('2').trigger();
	    });
	    $('#ClientExportCSV').on('click', function () {
	        ClientTable.button('3').trigger();
	    });
	    $('#ClientExportPDF').on('click', function () {
	        ClientTable.button('4').trigger();
    	});
	});
</script>
</html>