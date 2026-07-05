<?php 
    $languages = include __DIR__ . "/languages.php";
    $file = __DIR__ . "/config.json";
    if ( isset( $_POST['type'] ) ) {
        file_put_contents( $file, json_encode( $_POST ) );
    } 
    $data = [ 
        "type" => "TEXT",
        "languages" => []
    ];
    if( file_exists( $file ) ) {
        $data = json_decode(  file_get_contents($file), true );
    }
    
?>
<div class="wrap">
    <h1 class="wp-heading-inline">Tradução</h1>
    <div class="form-wrap">
        <h2>Escolha abaixo as linguagens suportadas</h2>
        <form action="?page=traducao%2Fadmin.php" method="POST" class="validate">
            <div style="column-count: 4">
                <?php foreach( $languages as $slug => $name ) :?>
                    <label for="c-<?= $slug ?>">
                        <?php  $checked = in_array($slug, $data['languages']) ? 'checked' : '' ?>
                        <input <?= $checked ?> name="languages[]" type="checkbox" id="c-<?= $slug ?>" value="<?= $slug ?>">            
                        <?= $name ?>
                    </label>
                <?php endforeach;?>
            </div>
            <div class="form-field term-parent-wrap">
                <label for="parent">Tipo de Visualização</label>
                <select name="type" class="postform">
                    <option value="TEXT" <?= $data['type'] == 'TEXT' ? 'selected' : '' ?> >TEXTO</option>
                    <option value="FLAG" <?= $data['type'] == 'FLAG' ? 'selected' : '' ?> >BANDEIRA</option>
                </select>
            </div>
            <p class="submit">
                <input type="submit" class="button button-primary" value="Salvar"> <span class="spinner"></span>
            </p>
        </form>
        <h2>Traduzindo o tema </h2>
        <p>
            para traduzir o tema é necessarion adicionar 2 chamadas de função 
            no arquivo <b>function.php</b> do seu tema
        </p>
        <p> _setDomain( 'message' ); </p>
        <p> _bindDomain( __DIR__ . '/locale' ); </p>
        <h2> Tradução de termo </h2>
        <p> __t('Hello', 'homePage') </p>
    </div>
</div>