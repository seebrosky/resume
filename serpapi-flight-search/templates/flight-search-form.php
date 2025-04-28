<form id="flight-search-form" class="flight-search-form" method="get" >

    <?php
    // calculate tomorrow’s date in YYYY-MM-DD
    $tomorrow = date( 'Y-m-d', strtotime( '+1 day' ) );
    
    // pull the submitted values (if any)
    $out_val = $_GET['outbound_date'] ?? $tomorrow;
    $dep_val = $_GET['departure_id'] ?? '';
    $arr_val = $_GET['arrival_id']   ?? '';
    $pax_val = $_GET['passengers']   ?? 1;
    ?>

    <!-- Force one-way searches: -->
    <input type="hidden" name="type" value="2">

    <div class="vc_container">
        <div class="vc_row form-wrapper">

            <div class="vc_col-md-6 vc_col-xs-12">
              <div class="form-group">
                <label for="departure_id">Departure City:</label>
                <div class="wpex-select-wrap">                
                  <select name="departure_id" id="departure_id" required tabindex="1">
                    <option value="">— Select Departure —</option>
                    <?php foreach( $cities as $iata => $label ): ?>
                      <option 
                        value="<?php echo esc_attr( $iata ); ?>"
                        <?php selected( $dep_val, $iata ); ?>
                      >
                        <?php echo esc_html( $label ); ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                  <span class="wpex-select-arrow">…</span>
                </div>
              </div>
            </div>            

            <div class="vc_col-md-6 vc_col-xs-12">
              <div class="form-group">
                <label for="arrival_id">Destination City:</label>
                <div class="wpex-select-wrap">                
                  <select name="arrival_id" id="arrival_id" required tabindex="2">
                    <option value="">— Select Arrival —</option>
                    <?php foreach( $cities as $iata => $label ): ?>
                      <option 
                        value="<?php echo esc_attr( $iata ); ?>"
                        <?php selected( $arr_val, $iata ); ?>
                      >
                        <?php echo esc_html( $label ); ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                  <span class="wpex-select-arrow">…</span>
                </div>
              </div>                    
            </div> 
            
            <div class="vc_col-md-6 vc_col-xs-12">
               <div class="form-group">
                    <label for="outbound_date">Departure Date:</label>
                    <input 
                        type="text" 
                        id="outbound_date" 
                        name="outbound_date" 
                        class="datepicker" 
                        required 
                        autocomplete="off"
                        autocorrect="off"
                        autocapitalize="off"
                        spellcheck="false"
                        value="<?php echo esc_attr( $out_val ); ?>"
                        tabindex="3"
                    /> 
                </div>                      
            </div>              

            <div class="vc_col-md-6 vc_col-xs-12">
                <div class="form-group">
                    <label for="passengers">Passengers:</label>
                    <input 
                      type="number" 
                      id="passengers"
                      name="passengers" 
                      value="<?php echo esc_attr( $pax_val ); ?>" 
                      min="1" 
                      tabindex="4"
                    > 
                </div>                        
            </div>     

        </div>

        <div class="vc_row">
            <div class="flight-finder-form-btn-wrapper vc_col-md-6 vc_col-xs-12">
                <button 
                  class="button theme-button" 
                  type="submit" 
                  name="search_flights" 
                  tabindex="5">
                    Search Flights
                </button>
            </div>
        </div>
    </div>
</form>