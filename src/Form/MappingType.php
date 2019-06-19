<?php
namespace App\Form;

use App\Entity\Client;
use App\Entity\Mapping;
use App\Entity\MediaType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MappingType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('id', NumberType::class, ['attr' =>
                [
                    'readonly' => true,
                ]
            ])
            ->add('rfid', TextType::class)
            ->add('additional_information', TextType::class)
            ->add('local_path', TextType::class)
            ->add('lms_path', TextType::class)
//            ->add('client', IntegerType::class)
//            ->add('media_type', TextType::class);

            ->add('client', EntityType::class, [
                'class' => Client::class,
                'choice_label' => 'name'
            ])
            ->add('media_type', EntityType::class, [
                'class' => MediaType::class,
                'choice_label' => 'name'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data-class' => Mapping::class
        ]);
    }
}