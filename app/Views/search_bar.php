<style>

    .custom-input-container {
        display: flex;
        align-items: center;
        border: 1px solid #d3d3d3;
        border-radius: 50px;
        padding: 5px 5px;
        background-color: white;
        margin-top:8px;
        direction: rtl;
    }
    
    .custom-select-container {
        display: flex;
        align-items: center;
        border: 1px solid #d3d3d3;
        border-radius: 50px;
        padding: 5px 10px;
        background-color: white;
        margin-top:8px;
        direction: rtl;
    }

    .custom-select-container select {
        border: none;
        background: transparent;
        font-size: 18px;
        font-weight:bold;
        flex: 1;
        outline: none;
        text-align:center;
        padding: 5px;
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
        cursor: pointer;
    }
    
    .custom-input-container input {
        border: none;
        background: transparent;
        font-size: 18px;
        font-weight:bold;
        flex: 1;
        outline: none;
        margin:0px;
        padding: 5px;
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
        cursor: pointer;
    }

    .custom-select-icon {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        background-color: #e0e0e0;
        display: flex;
        justify-content: center;
        align-items: center;
        margin-left: 5px;
    }

    .custom-select-icon::after {
        content: '>'; /* down arrow */
        font-weight:bold;
        font-size: 18px;
    }

</style>

<div class="col-md-12">            
    <form class="login-form form-horizontal" method="POST" target="_blank" id="form_login" action="<?= base_url('activities') ?>">
        <div class="form-container">
            <div class="col-lg-5 col-md-5 col-sm-5 col-xs-12">
                <div class="custom-input-container">
                    <input type="text" class="form-control" name="activity" placeholder="بحث عن نشاط">
                </div>
            </div>
            <div class="col-lg-5 col-md-5 col-sm-5 col-xs-12">
                <div class="custom-select-container">
                    <select name="city">
                        <?php foreach ($cities as $city): ?>
                            <option value="<?php echo $city->id; ?>"><?php echo $city->name; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="custom-select-icon"></div>
                </div>
            </div>
            <div class="col-lg-2 col-md-2 col-sm-2 col-xs-12">                    
                <button type="submit" class="register-button btn btn-lg btn-danger" style="border-radius: 50px;margin-right: 0px;margin-top: 8px">بحث </button>
            </div>
        </div>
    </form>
</div>