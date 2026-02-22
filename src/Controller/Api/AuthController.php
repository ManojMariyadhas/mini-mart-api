<?php

namespace App\Controller\Api;

use App\Entity\Otp;
use App\Repository\OtpRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use App\Repository\UserRepository;
use App\Entity\User;

class AuthController extends AbstractController
{
    #[Route('/api/auth/verify-otp', methods: ['POST'])]
    public function verifyOtp(
        Request $request,
        OtpRepository $otpRepository,
        UserRepository $userRepository,
        JWTTokenManagerInterface $jwtManager,
        EntityManagerInterface $em
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        $phone = $data['phone'] ?? null;
        $code  = $data['otp'] ?? null;

        if (!$phone || !$code) {
            return $this->json(['message' => 'Phone and OTP required'], 400);
        }

        // 🔍 Find valid OTP
        $otp = $otpRepository->findOneBy([
            'phone' => $phone,
            'code' => $code,
            'isUsed' => false,
        ]);

        if (!$otp) {
            return $this->json(['message' => 'Invalid OTP'], 400);
        }
        //Temporarly commmented
        // if ($otp->isExpired()) {
        //     return $this->json(['message' => 'OTP expired'], 400);
        // }

        // ✅ Mark OTP as used
        $otp->markUsed();
        $em->flush();

        // 👤 Find or create User
        $user = $userRepository->findOneBy(['phone' => $phone]);

        if (!$user) {
            $user = new User();
            $user->setPhone($phone);
            $em->persist($user);
            $em->flush();
        }

        // 🔐 Generate JWT
        $token = $jwtManager->create($user);

        return $this->json([
            'success' => true,
            'token' => $token,
            'phone' => $phone,
        ]);
    }


    #[Route('/api/auth/request-otp', methods: ['POST'])]
    public function requestOtp(
        Request $request,
        EntityManagerInterface $em
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $phone = $data['phone'] ?? null;

        if (!$phone) {
            return $this->json(['message' => 'Phone required'], 400);
        }

        $code = (string) random_int(100000, 999999);

        // for temporary purpose

        // Keep only digits (remove +91, spaces, etc.)
        $phoneDigits = preg_replace('/\D/', '', $phone);

        // Take last 6 digits
        $code = substr($phoneDigits, -6);

        $otp = new Otp($phone, $code);
        $em->persist($otp);
        $em->flush();

        // DEV ONLY
        return $this->json([
            'success' => true,
            'otp' => $code
        ]);
    }
}
