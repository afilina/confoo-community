<?php
namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

use Symfony\Component\Form\Extension\Core\Type as Type;

class AlertType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('tag', Type\HiddenType::class)
            ->add('email', Type\EmailType::class, ['label' => 'E-mail address'])
            ->add('frequency', Type\ChoiceType::class, [
                'label' => 'Frequency',
                'choices'  => array(
                    'daily' => 'daily',
                    'weekly' => 'weekly',
                ),])
            ->add('save', Type\SubmitType::class, ['label' => 'Subscribe'])
        ;
    }
}