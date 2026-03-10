<p>Por favor, acesse o sistema e siga as instruções para redefinir sua senha.</p>

<?php if(!empty($cpf_coordenador)): ?>
    <p><strong>CPF do Coordenador:</strong> <?php echo e($cpf_coordenador); ?></p>
<?php endif; ?>

<p>Se você não solicitou essa alteração, ignore este e-mail.</p>

<p>Atenciosamente,<br>CEDEC - MG</p>

<a href="<?php echo e(route('password.reset', ['token' => $token])); ?>">Clique aqui para redefinir sua senha</a>
<?php /**PATH /var/www/resources/views/auth/password_reset_simple.blade.php ENDPATH**/ ?>